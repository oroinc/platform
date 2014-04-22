/*global define*/
/*jslint nomen:true, browser:true*/
define(['jquery'], function ($) {
    'use strict';

    var $mask, onHide;

    function createMask() {
        $mask = $('<div></div>');
        $mask.attr('id', 'oro-dropdown-mask').attr('class', 'oro-dropdown-mask');
        $mask.hide();
        $mask.appendTo('body');
        $mask.on('mousedown touchstart click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $mask.hide();
            if (onHide) {
                onHide();
            }
        });
    }

    return {
        show: function () {
            if (!$mask) {
                createMask();
            }
            $mask.show();
            return {
                onhide: function (callback) {
                    onHide = callback;
                }
            };
        },

        hide: function () {
            if ($mask) {
                $mask.hide();
                onHide = null;
            }
        }
    };
});
