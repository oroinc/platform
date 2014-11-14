/*jslint nomen: true*/
/*global define*/
define(['underscore', 'orotranslation/js/translator', 'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function (_, __) {
    'use strict';

    return function (options) {
        var $customColor;
        options._sourceElement.simplecolorpicker(_.omit(options, ['_sourceElement', 'custom_color']));
        $customColor = options._sourceElement.parent().find('span.custom-color');
        if ($customColor.length) {
            $customColor.minicolors(_.defaults(options.custom_color, {
                control: 'wheel',
                letterCase: 'uppercase',
                hide: function () {
                    options._sourceElement.val($customColor.minicolors('value'));
                }
            }));
            $customColor.parent().find('.minicolors-panel').append(
                '<div class="form-actions">' +
                    '<button class="btn pull-right" data-action="cancel" type="button">' + __('Close') + '</button>' +
                '</div>'
            );
            $customColor.parent().find('button[data-action=cancel]').on('click', function (e) {
                e.preventDefault();
                $customColor.minicolors('hide');
            });
        }
    };
});
