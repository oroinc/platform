define(function(require) {
    'use strict';

    const $ = require('jquery');
    const backdropManager = require('./backdrop-manager');
    const mediator = require('oroui/js/mediator');

    const overlayTool = {
        createOverlay: function($overlayContent, options) {
            if (!options.insertInto) {
                options.insertInto = $(document.body);
            }
            if (!options.zIndex) {
                options.zIndex = 700;
            }
            options.insertInto.append($overlayContent);
            $overlayContent.css({
                zIndex: options.zIndex
            });
            this.updatePosition($overlayContent, options);
            const interval = setInterval(function() {
                if (!$overlayContent.data('interval')) {
                    // fix memory leak
                    clearInterval(interval);
                }
                overlayTool.updatePosition($overlayContent, options);
            }, 400);
            $overlayContent.data('interval', interval);
            let backdropId;
            if (options.backdrop) {
                backdropId = backdropManager.hold();
                $overlayContent.data('backdrop', backdropId);
            }
            const overlayControl = {
                remove: function() {
                    mediator.off('overlay:focus', onOverlayFocus);
                    $overlayContent.removeClass('overlay-focused');
                    $overlayContent.off('click.overlay-tool');
                    clearInterval(interval);
                    if (backdropId) {
                        backdropManager.release(backdropId);
                    }
                    overlayTool.removeOverlay($overlayContent);
                },
                focus: function() {
                    $overlayContent.addClass('overlay-focused');
                    $overlayContent.css({
                        zIndex: options.zIndex + 1
                    });
                    mediator.trigger('overlay:focus', $overlayContent);
                },
                blur: function() {
                    $overlayContent.removeClass('overlay-focused');
                    $overlayContent.css({
                        zIndex: options.zIndex
                    });
                    mediator.trigger('overlay:blur', $overlayContent);
                }
            };
            overlayControl.focus();
            function onOverlayFocus($content) {
                if ($content === $overlayContent) {
                    return;
                }
                overlayControl.blur();
            }
            $overlayContent.on('click.overlay-tool focus.overlay-tool', function() {
                overlayControl.focus();
            });
            mediator.on('overlay:focus', onOverlayFocus);

            return overlayControl;
        },

        updatePosition: function($overlayContent, options) {
            if (options.position) {
                let _new;
                let old;
                // try to find position for overlay in several iterations
                for (let i = 0; i < 5; i++) {
                    old = _new || ($overlayContent.css('top') + '.' + $overlayContent.css('left'));
                    $overlayContent.position(options.position);
                    _new = $overlayContent.css('top') + '.' + $overlayContent.css('left');
                    if (old === _new) {
                        break;
                    }
                    $overlayContent.trigger('updatePosition');
                }
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
