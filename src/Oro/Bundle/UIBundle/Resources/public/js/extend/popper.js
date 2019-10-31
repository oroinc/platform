define(function(require) {
    'use strict';

    const Popper = require('popper');

    Popper.Defaults.modifiers.adjustHeight = {
        order: 550,
        enabled: false,
        fn: function(data, options) {
            const scrollElement = data.instance.state.scrollElement;
            let clientRect;
            let availableHeight;
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
