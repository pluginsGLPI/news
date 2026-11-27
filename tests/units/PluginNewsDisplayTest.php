<?php

/**
 * -------------------------------------------------------------------------
 * News plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of News.
 *
 * News is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * News is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with News. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2023 by News plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/news
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\News\Tests\Units;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\DbTestCase;
use PluginNewsAlert;
use PluginNewsAlert_Target;
use Profile;
use Symfony\Component\DomCrawler\Crawler;
use User;

class PluginNewsDisplayTest extends DbTestCase
{
    private function createAlert(string $name): PluginNewsAlert
    {
        $alert = $this->createItem(PluginNewsAlert::class, [
            'name'                   => $name,
            'message'                => 'Alert message',
            'type'                   => PluginNewsAlert::GENERAL,
            'is_displayed_oncentral'        => 1,
            'is_displayed_onlogin'          => 0,
            'is_displayed_onhelpdesk'       => 0,
            'is_displayed_onservicecatalog' => 0,
            'display_dates'                 => 0,
            'is_close_allowed'              => 0,
            'is_active'              => 1,
            'entities_id'            => 0,
            'is_recursive'           => 1,
            'size'                   => 'medium',
            'icon'                   => 'settings',
            'background_color'       => 'white',
            'text_color'             => 'dark',
            'emphasis_color'         => 'dark',
        ]);
        $this->assertInstanceOf(PluginNewsAlert::class, $alert);

        return $alert;
    }

    private function addTarget(PluginNewsAlert $alert, string $itemtype, int $items_id): void
    {
        $this->createItem(PluginNewsAlert_Target::class, [
            'plugin_news_alerts_id' => $alert->getID(),
            'itemtype'              => $itemtype,
            'items_id'              => $items_id,
        ]);
    }

    private function getCentralAlertsOutput(): string
    {
        ob_start();
        PluginNewsAlert::displayOnCentral();

        return (string) ob_get_clean();
    }

    public function testCentralContainerIsEmptyWithoutAlert(): void
    {
        $this->login('glpi');

        $output = $this->getCentralAlertsOutput();

        $this->assertStringStartsWith('<tr><td colspan="2">', trim($output));
        // The container must stay :empty to be hidden by CSS
        $this->assertStringContainsString('<div class="plugin_news_alert-container row align-items-stretch"></div>', $output);
    }

    public function testCentralAlertIsDisplayedToTargetedProfiles(): void
    {
        $this->login('glpi');

        // A new alert targets all profiles by default
        $alert = $this->createAlert('Central alert for everyone');

        $output = $this->getCentralAlertsOutput();

        $this->assertStringContainsString('Central alert for everyone', $output);
        $this->assertStringContainsString('data-id="' . $alert->getID() . '"', $output);
        $this->assertStringNotContainsString('<div class="plugin_news_alert-container row align-items-stretch"></div>', $output);
    }

    public function testTargetsTabListsTargetsWithMassiveActions(): void
    {
        $this->login('glpi');

        // A new alert targets all profiles by default
        $alert = $this->createAlert('Alert with targets');
        $user_id = getItemByTypeName(User::class, 'tech', true);
        $this->addTarget($alert, User::class, $user_id);

        ob_start();
        PluginNewsAlert_Target::showForAlert($alert);
        $output = (string) ob_get_clean();

        // Add a target form
        $this->assertStringContainsString('name="plugin_news_alerts_id" value="' . $alert->getID() . '"', $output);
        $this->assertStringContainsString('dropdown_itemtype', $output);

        // One row per target, with its massive action checkbox
        $this->assertSame(2, substr_count($output, 'name="item[PluginNewsAlert_Target]['));
        $this->assertStringContainsString(Profile::getTypeName(1), $output);
        $this->assertStringContainsString('All', $output);
        $this->assertStringContainsString(User::getTypeName(1), $output);
        $this->assertStringContainsString('tech', $output);
    }

    /**
     * @param array<string, mixed> $alert_fields
     */
    private function renderAlert(array $alert_fields): Crawler
    {
        return new Crawler(TemplateRenderer::getInstance()->render('@news/display_alert.html.twig', [
            'alert_fields'           => $alert_fields + [
                'id'            => 1,
                'name'          => 'Alert',
                'icon'          => 'settings',
                'display_dates' => 0,
                'date_start'    => null,
                'date_end'      => null,
            ],
            'content'              => 'Alert message',
            'size'                   => 'medium',
            'can_close'              => true,
            'show_only_login_alerts' => true,
        ]));
    }

    public function testImportantAlertOnLightColorHasDarkText(): void
    {
        foreach (['yellow', 'lime'] as $color) {
            $alert = $this->renderAlert(['emphasis_color' => $color, 'is_important' => 1])->filter('.alert');
            $this->assertStringContainsString('plugin_news_alert-dark-text', $alert->attr('class') ?? '');
            $this->assertStringNotContainsString('btn-close-white', $alert->filter('.btn-close')->attr('class') ?? '');
        }

        // Other colors keep the white text of GLPI important alerts
        $alert = $this->renderAlert(['emphasis_color' => 'red', 'is_important' => 1])->filter('.alert');
        $this->assertStringNotContainsString('plugin_news_alert-dark-text', $alert->attr('class') ?? '');
        $this->assertStringContainsString('btn-close-white', $alert->filter('.btn-close')->attr('class') ?? '');

        // A normal alert on a light color already has a dark text
        $alert = $this->renderAlert(['emphasis_color' => 'yellow', 'is_important' => 0])->filter('.alert');
        $this->assertStringNotContainsString('plugin_news_alert-dark-text', $alert->attr('class') ?? '');
    }

    public function testAlertToggleIsAButtonAndDatesNeedAValue(): void
    {
        $crawler = $this->renderAlert(['emphasis_color' => 'blue', 'display_dates' => 1]);
        $this->assertCount(1, $crawler->filter('button.plugin_news_alert-toggle[type="button"][aria-label]'));
        // Without any date, the calendar icon would be displayed alone
        $this->assertCount(0, $crawler->filter('.ti-calendar-event'));

        $crawler = $this->renderAlert(['emphasis_color' => 'blue', 'display_dates' => 1, 'date_start' => '2026-11-19 09:00:00']);
        $this->assertCount(1, $crawler->filter('.ti-calendar-event'));
    }

    public function testTargetDropdownByType(): void
    {
        $this->login('glpi');

        foreach (['Group', 'Profile', 'User'] as $type) {
            $output = TemplateRenderer::getInstance()->render('@news/target_dropdown.html.twig', [
                'type'         => $type,
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]);

            $this->assertStringContainsString('name="items_id"', $output, $type);
            $this->assertStringContainsString('name="addvisibility"', $output, $type);
        }
    }
}
