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

function plugin_news_install()
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration(Plugin::getInfo('news', 'version'));

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    $alert_table = 'glpi_plugin_news_alerts';

    if (!$DB->tableExists($alert_table)) {
        $white  = PluginNewsAlert::WHITE;
        $dark   = PluginNewsAlert::DARK;
        $medium = PluginNewsAlert::MEDIUM;
        $DB->doQuery("
         CREATE TABLE IF NOT EXISTS `$alert_table` (
         `id`                       INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `date_mod`                 TIMESTAMP NOT NULL,
         `name`                     VARCHAR(255) NOT NULL,
         `message`                  TEXT NOT NULL,
         `date_start`               TIMESTAMP NULL DEFAULT NULL,
         `date_end`                 TIMESTAMP NULL DEFAULT NULL,
         `type`                     INT NOT NULL,
         `is_deleted`               TINYINT NOT NULL DEFAULT 0,
         `is_displayed_onlogin`     TINYINT NOT NULL,
         `is_displayed_oncentral`   TINYINT NOT NULL,
         `display_dates`            TINYINT NOT NULL DEFAULT 1,
         `background_color`         VARCHAR(255) NOT NULL DEFAULT '$white',
         `text_color`               VARCHAR(255) NOT NULL DEFAULT '$dark',
         `emphasis_color`           VARCHAR(255) NOT NULL DEFAULT '$dark',
         `size`                     VARCHAR(255) NOT NULL DEFAULT '$medium',
         `icon`                     VARCHAR(255) NOT NULL,
         `entities_id`              INT {$default_key_sign} NOT NULL,
         `is_recursive`             TINYINT NOT NULL DEFAULT 1,
         PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
      ");
    } else {
        $DB->update(
            PluginNewsAlert_User::getTable(),
            [
                'state' => PluginNewsAlert_User::VISIBLE,
            ],
            [
                'AND' => [
                    'state'                                           => PluginNewsAlert_User::HIDDEN,
                    PluginNewsAlert::getTable() . '.is_close_allowed' => 0,
                ],
            ],
            [
                'JOIN' => [
                    PluginNewsAlert::getTable() => [
                        'FKEY' => [
                            PluginNewsAlert_User::getTable() => 'plugin_news_alerts_id',
                            PluginNewsAlert::getTable()      => 'id',
                        ],
                    ],
                ],
            ],
        );
    }

    if (!$DB->tableExists('glpi_plugin_news_alerts_users')) {
        $DB->doQuery("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts_users` (
         `id`                    INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `plugin_news_alerts_id` INT {$default_key_sign} NOT NULL,
         `users_id`              INT {$default_key_sign} NOT NULL,
         `state`                 TINYINT NOT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `state_for_user`
            (`plugin_news_alerts_id`,`users_id`,`state`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
      ");
    }

    if (!$DB->tableExists('glpi_plugin_news_alerts_targets')) {
        $DB->doQuery("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts_targets` (
         `id`                    INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `plugin_news_alerts_id` INT {$default_key_sign} NOT NULL,
         `itemtype`              VARCHAR(255) NOT NULL,
         `items_id`              INT {$default_key_sign} NOT NULL,
         `all_items`             TINYINT NOT NULL DEFAULT 0,
         PRIMARY KEY (`id`),
         UNIQUE KEY `alert_itemtype_items_id`
            (`plugin_news_alerts_id`, `itemtype`,`items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
      ");
    }

    /* Remove old table */
    if ($DB->tableExists('glpi_plugin_news_profiles')) {
        $DB->doQuery('DROP TABLE IF EXISTS `glpi_plugin_news_profiles`;');
    }

    // add displayed on login flag
    if (!$DB->fieldExists($alert_table, 'is_displayed_onlogin')) {
        $migration->addField($alert_table, 'is_displayed_onlogin', 'bool');
    }

    // add displayed on helpdesk flag
    if (!$DB->fieldExists($alert_table, 'is_displayed_onhelpdesk')) {
        $migration->addField($alert_table, 'is_displayed_onhelpdesk', 'bool');
    }

    if (!$DB->fieldExists($alert_table, 'date_creation')) {
        if ($migration->addField($alert_table, 'date_creation', 'date')) {
            $migration->addKey($alert_table, 'date_creation');
        }
    }

    // add close allowed flag
    if (!$DB->fieldExists($alert_table, 'is_close_allowed')) {
        $migration->addField($alert_table, 'is_close_allowed', 'bool');
    }

    // add type field on alert (to display icons)
    if (!$DB->fieldExists($alert_table, 'type')) {
        $migration->addField($alert_table, 'type', 'integer');
    }

    // add activity flag
    if (!$DB->fieldExists($alert_table, 'is_active')) {
        if ($migration->addField($alert_table, 'is_active', 'bool')) {
            $migration->addKey($alert_table, 'is_active');
        }
    }

    // fix is_default default value
    $alert_fields = $DB->listFields($alert_table);
    if ($alert_fields['is_deleted']['Default'] !== '0') {
        $migration->changeField(
            $alert_table,
            'is_deleted',
            'is_deleted',
            'TINYINT NOT NULL DEFAULT 0',
        );
    }

    // end/start dates can be null
    $migration->changeField(
        $alert_table,
        'date_end',
        'date_end',
        'TIMESTAMP NULL DEFAULT NULL',
    );
    $migration->changeField(
        $alert_table,
        'date_start',
        'date_start',
        'TIMESTAMP NULL DEFAULT NULL',
    );

    if ($DB->fieldExists($alert_table, 'profiles_id')) {
        // migration of direct profiles into targets table
        $query_targets = "INSERT INTO glpi_plugin_news_alerts_targets
                           (plugin_news_alerts_id, itemtype, items_id)
                           SELECT id, 'Profile', profiles_id
                           FROM $alert_table";
        $DB->doQuery($query_targets);

        //drop old field
        $migration->dropField($alert_table, 'profiles_id');
    }

    // Replace -1 value usage in items_id foreign key
    if (!$DB->fieldExists('glpi_plugin_news_alerts_targets', 'all_items')) {
        $migration->addField('glpi_plugin_news_alerts_targets', 'all_items', 'bool');
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_plugin_news_alerts_targets',
                ['items_id' => '0', 'all_items' => '1'],
                ['items_id' => '-1'],
            ),
        );
    }

    // install default display preferences
    $dpreferences = new DisplayPreference();
    $found_dpref  = $dpreferences->find(['itemtype' => ['LIKE', '%PluginNews%']]);
    if (count($found_dpref) == 0) {
        $DB->doQuery("INSERT INTO `glpi_displaypreferences`
                     (`itemtype`, `num`, `rank`, `users_id`)
                  VALUES
                     ('PluginNewsAlert', 2, 1, 0),
                     ('PluginNewsAlert', 3, 2, 0),
                     ('PluginNewsAlert', 6, 4, 0)");
    }

    // add displayed on central flag
    if (!$DB->fieldExists($alert_table, 'is_displayed_oncentral')) {
        $migration->addField(
            $alert_table,
            'is_displayed_oncentral',
            'bool',
            ['value' => '1'],
        );
    }

    // Add background_color field
    if (!$DB->fieldExists($alert_table, 'background_color')) {
        $migration->addField(
            $alert_table,
            'background_color',
            'string',
            ['value' => PluginNewsAlert::WHITE],
        );
    }

    // Add text_color field
    if (!$DB->fieldExists($alert_table, 'text_color')) {
        $migration->addField(
            $alert_table,
            'text_color',
            'string',
            ['value' => PluginNewsAlert::DARK],
        );
    }

    // Add emphasis_color field
    if (!$DB->fieldExists($alert_table, 'emphasis_color')) {
        $migration->addField(
            $alert_table,
            'emphasis_color',
            'string',
            ['value' => PluginNewsAlert::DARK],
        );
    }

    // Add size field
    if (!$DB->fieldExists($alert_table, 'size')) {
        $migration->addField(
            $alert_table,
            'size',
            'string',
            ['value' => PluginNewsAlert::MEDIUM],
        );
    }

    // Add icon field
    if (!$DB->fieldExists($alert_table, 'icon')) {
        $migration->addField(
            $alert_table,
            'icon',
            'string',
            ['value' => ''],
        );
    }

    // Add display_dates field
    if (!$DB->fieldExists($alert_table, 'display_dates')) {
        $migration->addField(
            $alert_table,
            'display_dates',
            'bool',
            ['value' => '1'],
        );
    }

    // Build or rebuild templates data
    // -> Will fill new columns (colors + icon) with the expected values for each
    //    templates
    // -> Will update template values if changed in future updates
    foreach (array_keys(PluginNewsAlert::getTypes()) as $type) {
        $migration->addPostQuery(
            $DB->buildUpdate(
                $alert_table,
                PluginNewsAlert::getTemplatesValues()[$type],
                ['type' => $type],
            ),
        );
    }

    // $migration->addRight() does not allow to copy an existing right, we must write some custom code
    $right_exist = countElementsInTable(
        'glpi_profilerights',
        ['name' => PluginNewsAlert::$rightname],
    ) > 0;

    // Add the same standard rights on alerts as the rights already granted on
    // public reminders
    if (!$right_exist) {
        $reminder_rights = $DB->request([
            'SELECT' => ['profiles_id', 'rights'],
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => ['name' => 'reminder_public'],
        ]);

        foreach ($reminder_rights as $row) {
            $profile_id  = $row['profiles_id'];
            $right_value = $row['rights'] & ALLSTANDARDRIGHT;

            $migration->addPostQuery($DB->buildInsert('glpi_profilerights', [
                'profiles_id' => $profile_id,
                'rights'      => $right_value,
                'name'        => PluginNewsAlert::$rightname,
            ]));

            if (($_SESSION['glpiactiveprofile']['id'] ?? null) === $profile_id) {
                // Ensure menu will be displayed as soon as right is added.
                $_SESSION['glpiactiveprofile'][PluginNewsAlert::$rightname] = $right_value;
                unset($_SESSION['glpimenu']);
            }
        }
    }

    $migration->executeMigration();

    return true;
}

function plugin_news_uninstall()
{
    /** @var DBmysql $DB */
    global $DB;

    $DB->doQuery('DROP TABLE IF EXISTS `glpi_plugin_news_alerts`;');
    $DB->doQuery('DROP TABLE IF EXISTS `glpi_plugin_news_profiles`;');
    $DB->doQuery('DROP TABLE IF EXISTS `glpi_plugin_news_alerts_users`;');
    $DB->doQuery('DROP TABLE IF EXISTS `glpi_plugin_news_alerts_targets`;');
    $DB->doQuery("DELETE FROM `glpi_profiles` WHERE `name` LIKE '%plugin_news%';");
    $DB->doQuery("DELETE FROM `glpi_displaypreferences` WHERE `itemtype` LIKE '%PluginNews%';");

    return true;
}
