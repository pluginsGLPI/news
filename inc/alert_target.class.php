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

use Glpi\Application\View\TemplateRenderer;

// @codingStandardsIgnoreStart
class PluginNewsAlert_Target extends CommonDBTM
{
    // @codingStandardsIgnoreEnd
    public static string $rightname = 'plugin_news_alert';

    public static function getTypeName($nb = 0)
    {
        return _sn('Target', 'Targets', $nb, 'news');
    }

    public static function canDelete(): bool
    {
        return self::canUpdate();
    }

    public static function canPurge(): bool
    {
        return self::canUpdate();
    }

    public function addNeededInfoToInput($input)
    {
        if (
            $input['itemtype']    == 'Profile'
            && $input['items_id'] == -1
        ) {
            $input['all_items'] = 1;
            $input['items_id']  = 0;
        }

        return $input;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'items_id':
                if (
                    isset($values['itemtype'])
                    && is_subclass_of($values['itemtype'], 'CommonDBTM')
                ) {
                    $item = new $values['itemtype']();
                    if (
                        $values['itemtype']     == 'Profile'
                        && $values['all_items'] == 1
                    ) {
                        return $item->getTypeName() . ' - ' . __s('All', 'news');
                    }
                    $item->getFromDB($values['items_id']);

                    return $item->getTypeName() . ' - ' . $item->getName();
                }
                break;
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof PluginNewsAlert) {
            $nb = countElementsInTable(
                self::getTable(),
                ['plugin_news_alerts_id' => $item->getID()],
            );

            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof PluginNewsAlert) {
            self::showForAlert($item);
        }

        return true;
    }

    public static function showForAlert(PluginNewsAlert $alert)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $entries = [];
        $target  = new self();
        foreach ($target->find(['plugin_news_alerts_id' => $alert->getID()]) as $current_target) {
            if (is_a($current_target['itemtype'], CommonDBTM::class, true)) {
                $item = new $current_target['itemtype']();
                $item->getFromDB($current_target['items_id']);
                $name = ($current_target['all_items'] == 1
                    && $current_target['itemtype']    == 'Profile')
                       ? __('All', 'news')
                       : $item->getName(['complete' => true]);

                $entries[] = [
                    'itemtype'  => self::class,
                    'id'        => $current_target['id'],
                    'type'      => $item->getTypeName(),
                    'recipient' => $name,
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@news/alert_targets.html.twig', [
            'alert'          => $alert,
            'rand'           => mt_rand(),
            'canedit'        => $alert->canUpdateItem(),
            'entries'        => $entries,
            'targets_url'    => $CFG_GLPI['root_doc'] . '/plugins/news/ajax/targets.php',
            'targets_params' => [
                'entities_id'  => $alert->fields['entities_id'],
                'is_recursive' => $alert->fields['is_recursive'],
            ],
        ]);

        return true;
    }

    public static function getIcon()
    {
        return 'ti ti-target';
    }
}
