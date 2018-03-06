define(function(require) {
    'use strict';

    var SwipeActionsManager;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    SwipeActionsManager = function() {
        $('body').swipe({
            swipe: _.bind(this._onSwipe, this),
            threshold: 40
        });
    };

    SwipeActionsManager.prototype = {
        _onSwipe: function(event, direction, distance, duration, fingerCount, fingerData) {
            mediator.trigger('swipe-action-' + direction, _.toArray(arguments));
        }
    };

    return SwipeActionsManager;
});
