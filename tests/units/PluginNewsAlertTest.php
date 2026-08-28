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
use PluginNewsAlert_User;
use User;

class PluginNewsAlertTest extends DbTestCase
{
    private function getAlertUserState(int $au_id): int
    {
        $alert_user = new PluginNewsAlert_User();
        $this->assertTrue($alert_user->getFromDB($au_id));
        return (int) $alert_user->fields['state'];
    }

    public function testPostUpdateItemResetsHiddenAlerts(): void
    {
        $this->login('glpi');

        $alert = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'closable Alert',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $alert_id = $alert->getID();

        $user_1 = $this->createItem(
            User::class,
            [
                'name'      => 'user 1',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );

        $user_2   = $this->createItem(
            User::class,
            [
                'name'      => 'user 2',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );

        $user_3   = $this->createItem(
            User::class,
            [
                'name'      => 'user 3',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );

        $user_1_id = $user_1->getID();
        $user_2_id = $user_2->getID();
        $user_3_id = $user_3->getID();

        $alert_user_1 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $alert_user_2 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_id,
            'users_id'              => $user_2_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        //user 3 has not hidden the alert
        $alert_user_3 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_id,
            'users_id'              => $user_3_id,
            'state'                 => PluginNewsAlert_User::VISIBLE,
        ]);

        $alert_user_1_id = $alert_user_1->getID();
        $alert_user_2_id = $alert_user_2->getID();
        $alert_user_3_id = $alert_user_3->getID();

        $this->updateItem(PluginNewsAlert::class, $alert_id, ['name' => 'Alert with hidden users (updated)']);

        //assert that both users are now in VISIBLE state
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_2_id));

        //assert that user 3 is still in VISIBLE state
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_3_id));

        //re-hide users 1 and 2 alerts
        $this->updateItem(PluginNewsAlert_User::class, $alert_user_1_id, ['state' => PluginNewsAlert_User::HIDDEN]);
        $this->updateItem(PluginNewsAlert_User::class, $alert_user_2_id, ['state' => PluginNewsAlert_User::HIDDEN]);

        //update the alert with is_close_allowed = 0
        $this->updateItem(PluginNewsAlert::class, $alert_id, ['is_close_allowed' => 0]);

        //assert that alerts are visible again
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_2_id));

        //assert that user 3 is still in VISIBLE state
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_3_id));
    }

    public function testPostUpdateItemDoesNotAffectOtherAlerts(): void
    {
        $this->login('glpi');

        $alert_1 = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'alert 1',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $alert_1_id = $alert_1->getID();

        $alert_2 = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'alert 2',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $alert_2_id = $alert_2->getID();

        $user_1 = $this->createItem(
            User::class,
            [
                'name'      => 'user 1',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );
        $user_1_id = $user_1->getID();

        $alert_user_1 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_1_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $alert_user_2 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_2_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $alert_user_1_id = $alert_user_1->getID();
        $alert_user_2_id = $alert_user_2->getID();

        $this->updateItem(PluginNewsAlert::class, $alert_1_id, ['name' => 'Alert 1 (updated)']);

        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::HIDDEN, $this->getAlertUserState($alert_user_2_id));
    }

    /**
     * The alert preview script reads the current field values from their
     * 'plugin-news-alert-field' wrapper rather than from the surrounding <form>,
     * because that <form> is only rendered when the item is editable (a recursive
     * alert consulted from a sub-entity is shown read-only, with no <form> at all).
     * This asserts that the fields keep that marker, and stay usable (not disabled,
     * carrying their real value), whether or not the item is editable.
     */
    public function testAlertPreviewDisplaysWhetherYouCanEditOrNot(): void
    {
        $this->login('glpi');

        $alert = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'                          => 'Recursive alert',
                'message'                       => 'Alert preview content',
                'type'                          => 1,
                'size'                          => 'medium',
                'icon'                          => 'settings',
                'background_color'              => 'white',
                'text_color'                    => 'dark',
                'emphasis_color'                => 'dark',
                'is_active'                     => 1,
                'entities_id'                   => 0,
                'is_close_allowed'              => 1,
                'is_displayed_onlogin'          => 0,
                'is_displayed_oncentral'        => 0,
                'is_displayed_onhelpdesk'       => 0,
                'is_displayed_onservicecatalog' => 0,

            ],
        );

        $render = function () use ($alert): string {
            return TemplateRenderer::getInstance()->render('@news/alert_form.html.twig', [
                'item'             => $alert,
                'templates'        => PluginNewsAlert::getTypes(),
                'sizes'            => PluginNewsAlert::getSizes(),
                'colors'           => PluginNewsAlert::getColors(),
                'icons'            => PluginNewsAlert::getIcons(),
                'templates_values' => PluginNewsAlert::getTemplatesValues(),
            ]);
        };

        //Both scenarios will ensure that plugin-news-alert-field class is present,
        // as it ensures our fields values are carried to the backend.

        // First scenario: Render template in editable mode, we check the form is present.
        $editable_html = $render();
        $this->assertStringContainsString('name="asset_form"', $editable_html);
        $this->assertStringContainsString('plugin-news-alert-field', $editable_html);
        $this->assertStringContainsString('name="message"', $editable_html);
        $this->assertStringContainsString('Alert preview content', $editable_html);

        // Second scenario: Render template in readonly mode, we check the form is absent
        $profile = $this->createItem(\Profile::class, [
            'name'      => 'News read-only',
            'interface' => 'central',
        ]);
        $readonly_user = $this->createItem(User::class, [
            'name' => 'news_readonly_user',
        ]);
        $this->createItem(\Profile_User::class, [
            'users_id'    => $readonly_user->getID(),
            'profiles_id' => $profile->getID(),
            'entities_id' => 0,
        ]);

        global $DB;
        $DB->update(\ProfileRight::getTable(), ['rights' => 0], ['profiles_id' => $profile->getID()]);
        \ProfileRight::updateProfileRights($profile->getID(), [
            PluginNewsAlert::$rightname => READ,
        ]);

        $this->login('news_readonly_user');
        $this->assertFalse($alert->canEdit($alert->getID()));

        $readonly_html = $render();
        $this->assertStringNotContainsString('name="asset_form"', $readonly_html);
        $this->assertStringContainsString('plugin-news-alert-field', $readonly_html);
        $this->assertStringContainsString('name="message"', $readonly_html);
        $this->assertStringContainsString('Alert preview content', $readonly_html);

        // The checked "background_color" radio must not be disabled, or jQuery's
        // serialize() (used by the preview script) would silently drop it.
        $found = preg_match('/<input[^>]*name="background_color"[^>]*checked[^>]*>/', $readonly_html, $matches);
        $this->assertSame(1, $found);
        $this->assertStringNotContainsString('disabled', $matches[0]);
    }
}
