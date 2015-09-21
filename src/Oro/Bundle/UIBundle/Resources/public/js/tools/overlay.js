define(function(require) {
    'use strict';
    var $ = require('jquery');
    return {
        createOverlay: function($overlayContent, options) {
            var _this = this;
            $('body').addClass('backdrop');
            $(document.body).append($overlayContent);
            $overlayContent.css({
                zIndex: 10000
            });
            if (options.position) {
                $overlayContent.position(options.position);
            }
            $overlayContent.data('interval', setInterval(function() {
                if (options.position) {
                    $overlayContent.position(options.position);
                }
            }, 400));
            return {
                remove: function() {
                    _this.removeOverlay($overlayContent);
                }
            };
        },

        removeOverlay: function($overlayContent) {
            if ($overlayContent.data('interval')) {
                clearInterval($overlayContent.data('interval'));
            }
            $overlayContent.remove();
            $('body').removeClass('backdrop');
        }
    };
});
