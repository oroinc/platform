/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function ($, _, __) {
    'use strict';

    return function (options) {
        var $parent = options._sourceElement.parent(),
            pickerId = options._sourceElement.prop('id') + '_picker',
            $picker,
            $current;

        options._sourceElement.simplecolorpicker(_.omit(options, ['_sourceElement', 'picker']));

        $parent.append('<span id="' + pickerId + '" style="display: none;"></span>');
        $picker = $parent.find('#' + pickerId);
        $picker.minicolors(_.defaults(options.picker, {
            control: 'wheel',
            letterCase: 'uppercase',
            change: function(hex, opacity) {
                if ($current) {
                    $current.css('background-color', hex);
                }
            },
            hide: function () {
                if ($current) {
                    options._sourceElement.simplecolorpicker(
                        'replaceColor',
                        $current.data('color'),
                        $picker.minicolors('value')
                    );
                    $current = null;
                }
            }
        }));
        $picker.parent().find('.minicolors-panel').append(
            '<div class="form-actions">' +
                '<button class="btn pull-right" data-action="cancel" type="button">' + __('Close') + '</button>' +
            '</div>'
        );
        $picker.parent().find('button[data-action=cancel]').on('click', function (e) {
            e.preventDefault();
            $picker.minicolors('hide');
        });
        $picker.siblings('.minicolors').css({'position': 'static', 'display': 'block'});

        $parent.on('click', 'span.color', function (e) {
            e.preventDefault();
            if (!options._sourceElement.is(':disabled')) {
                $current = $(e.currentTarget);
                var $panel = $picker.parent().find('.minicolors-panel'),
                    pos = $current.position(),
                    x = pos.left + 5,
                    y = pos.top + $current.outerHeight() + 3,
                    w = $panel.outerWidth(),
                    h = $panel.outerHeight() + 39,
                    width = $current.offsetParent().width(),
                    height = $current.offsetParent().height();
                if (x > width - w) {
                    x -= w;
                }
                if (y > height - h) {
                    y -= h + $current.outerHeight() + 6;
                }
                $panel.css({'left': x, 'top': y});
                $picker.parent().removeClass('minicolors-focus');
                $picker.minicolors('value', $current.data('color'));
                $picker.minicolors('show');
            }
        });
    };
});
