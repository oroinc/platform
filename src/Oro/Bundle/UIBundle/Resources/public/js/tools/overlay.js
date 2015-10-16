define(function(require) {
    'use strict';
    var $ = require('jquery');
    var backdropManager = require('./backdrop-manager');
    var overlayTool = {

        createOverlay: function($overlayContent, options) {
            $(document.body).append($overlayContent);
            $overlayContent.css({
                zIndex: 10000
            });
            this.updatePosition($overlayContent, options);
            var interval = setInterval(function() {
                if (!$overlayContent.data('interval')) {
                    // fix memory leak
                    clearInterval(interval);
                }
                overlayTool.updatePosition($overlayContent, options);
            }, 400);
            $overlayContent.data('interval', interval);
            if (options.backdrop) {
                $overlayContent.data('backdrop', backdropManager.hold());
            }
            return {
                remove: function() {
                    clearInterval(interval);
                    overlayTool.removeOverlay($overlayContent);
                }
            };
        },

        updatePosition: function($overlayContent, options) {
            if (options.position) {
                var _new;
                var old;
                var iterations = 5;
                // try to find position for overlay in several iterations
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
            if ($overlayContent.data('backdrop')) {
                backdropManager.release($overlayContent.data('backdrop'));
            }
            $overlayContent.remove();
        }
    };
    return overlayTool;
});
