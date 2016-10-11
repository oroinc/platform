define(['jquery'], function($) {
    'use strict';

    var $mask;
    var onHide;

    function createMask() {
        $mask = $('<div></div>');
        $mask.attr('id', 'oro-dropdown-mask').attr('class', 'oro-dropdown-mask');
        $mask.hide();
        $mask.appendTo('body');
        $mask.on('mousedown touchstart click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $mask.hide();
            if (onHide) {
                onHide();
            }
        });
    }

    return {
        show: function(zIndex) {
            if (!$mask) {
                createMask();
            }
            $mask.css('zIndex', zIndex === void 0 ? '' : zIndex).show();
            return {
                onhide: function(callback) {
                    onHide = callback;
                }
            };
        },

        hide: function() {
            if ($mask) {
                $mask.hide();
                onHide = null;
            }
        }
    };
});
