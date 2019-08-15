define(function(require) {
    'use strict';

    var Popper = require('popper');

    Popper.Defaults.modifiers.adjustHeight = {
        order: 550,
        enabled: false,
        fn: function(data, options) {
            var scrollElement = data.instance.state.scrollElement;
            var clientRect;
            var availableHeight;
            if (scrollElement.tagName.toUpperCase() === 'BODY') {
                availableHeight = scrollElement.parentElement.clientHeight - data.popper.top;
            } else {
                clientRect = scrollElement.getBoundingClientRect();
                availableHeight = clientRect.top + clientRect.height - data.popper.top;
            }

            if (data.popper.height > availableHeight) {
                data.styles.maxHeight = availableHeight + 'px';
                data.attributes['x-adjusted-height'] = '';
            } else {
                data.styles.maxHeight = 'none';
                data.attributes['x-adjusted-height'] = false;
            }
            return data;
        }
    };

    return Popper;
});
