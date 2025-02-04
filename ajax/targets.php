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

$AJAX_INCLUDE = 1;
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['type']) && !empty($_POST['type'])) {
    echo "<table class='tab_format'>";
    echo '<tr>';
    echo '<td>';
    switch ($_POST['type']) {
        case 'User':
            User::dropdown(['name' => 'items_id',
                'right'            => 'all',
                'entity'           => $_POST['entities_id'],
                'entity_sons'      => $_POST['is_recursive'],
            ]);
            break;

        case 'Group':
            Group::dropdown(['name' => 'items_id']);
            break;

        case 'Profile':
            Profile::dropdown(['name' => 'items_id',
                'toadd'               => [-1 => __('All', 'news')],
            ]);
            break;
    }
    echo '</td>';
    echo "<td><input type='submit' name='addvisibility' value=\"" . _sx('button', 'Add', 'news') . "\"
                   class='submit'></td>";
    echo '</tr>';
    echo '</table>';
}
