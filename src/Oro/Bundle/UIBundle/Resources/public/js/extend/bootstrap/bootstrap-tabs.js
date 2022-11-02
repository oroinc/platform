define(function(require) {
    'use strict';

    const $ = require('jquery');
    const DATA_KEY = 'bs.tab';
    const EVENT_KEY = '.' + DATA_KEY;
    const Event = {
        HIDDEN: 'hidden' + EVENT_KEY,
        SHOWN: 'shown' + EVENT_KEY
    };
    const ClassName = {
        ACTIVE: 'active',
        SHOW: 'show'
    };

    const mediator = require('oroui/js/mediator');
    const Util = require('bootstrap-util');

    $(document)
        .on(Event.HIDDEN, function(event) {
            const prevEl = $(event.relatedTarget);

            // Remove active state from element which placed outside of NAV_LIST_GROUP container
            if (prevEl.data('extra-toggle') === 'tab') {
                prevEl
                    .removeClass(ClassName.SHOW + ' ' + ClassName.ACTIVE)
                    .attr('aria-selected', false);
            }
            const selector = Util.getSelectorFromElement(event.target);
            mediator.trigger('content:hidden', $(selector));
        })
        .on(Event.SHOWN, function(event) {
            const selector = Util.getSelectorFromElement(event.target);
            mediator.trigger('content:shown', $(selector));
            mediator.trigger('layout:reposition');
        });
});
