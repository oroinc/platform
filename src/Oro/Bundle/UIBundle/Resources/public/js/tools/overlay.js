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
            this.updatePosition($overlayContent, options);
            $overlayContent.data('interval', setInterval(function() {
                _this.updatePosition($overlayContent, options);
            }, 400));
            return {
                remove: function() {
                    _this.removeOverlay($overlayContent);
                }
            };
        },

        updatePosition: function($overlayContent, options) {
            if (options.position) {
                var _new;
                var old;
                var iterations = 5;
                do {
                    old = _new || ($overlayContent.css('top') + '.' + $overlayContent.css('left'));
                    $overlayContent.position(options.position);
                    _new = $overlayContent.css('top') + '.' + $overlayContent.css('left');
                    iterations--;
                } while (old !== _new && iterations > 0);
            }
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
