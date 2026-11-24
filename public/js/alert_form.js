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

function newsInitAlertForm(root) {
    const previewUrl = root.dataset.newsPreviewUrl;
    const templatesValues = JSON.parse(root.dataset.newsTemplatesValues);
    const loadPreviewOnInit = root.dataset.newsLoadPreview === '1';

    (async function () {
        const $fields = $(".plugin-news-alert-field");

        const reload_preview = function () {
            // Show preview section if hidden
            $('.preview-section').removeClass('d-none');

            // Send tinymce content to the forms textarea
            tinyMCE.triggerSave();

            $(".alert-preview").load(
                previewUrl,
                $fields.find(':input').serialize()
            );
        };

        // Typed texts refresh the preview once written, when they lose the focus ("change" event, see below),
        // instead of on each key; a click (color, icon, dropdown, ...) refreshes it immediately
        const is_typed_input = function (input) {
            return $(input).is('textarea, input:not([type="radio"], [type="checkbox"])');
        };

        // Any input or dropdown change on our form
        let updating_template = false;
        $fields.find(':input').on('input', function (e) {
            if (updating_template) {
                // Prevent infinite loops as modifying the template will trigger
                // an update on others fields
                return;
            }

            if ($(e.target).prop('name') == 'type') {
                // Template change
                updating_template = true;

                // Template disabled, stop processing
                if ($(e.target).val() == "0") {
                    updating_template = false;
                    return;
                }

                // Clear values
                $fields.find('input[name="icon"]').prop('checked', false);
                $fields.find('input[name="emphasis_color"]').prop('checked', false);

                // Apply template values
                const values = templatesValues[$(e.target).val()];
                if (values) {
                    $fields.find('input[name="icon"][value="' + values.icon + '"]').prop('checked', true);
                    $fields.find('select[name="is_important"]').val(values.is_important).trigger('change');
                    $fields.find('input[name="emphasis_color"][value="' + values.emphasis_color + '"]').prop('checked', true);
                }

                updating_template = false;
            } else if (['icon', 'emphasis_color', 'is_important'].includes($(e.target).prop('name'))) {
                // Manual color or icon change, disable templating
                $fields.find('select[name=type]').val(0).trigger("change");
            }

            if (!is_typed_input(e.target)) {
                reload_preview();
            }
        });
        $fields.find(':input').on('change', function (e) {
            if (is_typed_input(e.target)) {
                reload_preview();
            }
        });

        // Specific tinymce change detection: "change" is triggered when the editor loses the focus or when a
        // command (formatting, paste, ...) is applied, not on cursor moves
        await new Promise(r => setTimeout(r, 500)); // Wait for all editors to be loaded
        let i = 0;
        let editor;
        while ((editor = tinymce.get(i++))) {
            editor.on('change', function () {
                reload_preview();
            });
        }

        if (loadPreviewOnInit) {
            // If existing alert, load preview immediatly
            // New alerts will not have a preview until a change is made on any inputs
            reload_preview();
        }
    })();
}

document.querySelectorAll('[data-news-preview-url]:not([data-news-alert-form-ready])').forEach(function (root) {
    root.dataset.newsAlertFormReady = '';
    newsInitAlertForm(root);
});
