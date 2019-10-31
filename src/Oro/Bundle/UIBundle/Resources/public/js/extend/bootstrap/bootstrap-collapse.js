define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const persistentStorage = require('oroui/js/persistent-storage');
    const mediator = require('oroui/js/mediator');
    require('bootstrap-collapse');

    const DATA_KEY = 'bs.collapse';
    const EVENT_KEY = '.' + DATA_KEY;
    const DATA_API_KEY = '.data-api';
    const Event = {
        SHOW: 'show' + EVENT_KEY,
        SHOWN: 'shown' + EVENT_KEY,
        HIDE: 'hide' + EVENT_KEY,
        HIDDEN: 'hidden' + EVENT_KEY,
        CLICK_DATA_API: 'click' + EVENT_KEY + DATA_API_KEY
    };

    const STATE_ID_DATA_KEY = 'data-collapse-state-id';
    const Collapse = $.fn.collapse.Constructor;
    const original = _.pick(Collapse.prototype, 'show', 'hide');

    function titleUpdate(state, el) {
        const attr = 'data-' + state + '-title';

        if (el.hasAttribute(attr)) {
            el.setAttribute('title', el.getAttribute(attr));
        }
    }

    function setState(triggerArray, state) {
        const stateIdHolder = _.find(triggerArray, function(el) {
            return el.hasAttribute(STATE_ID_DATA_KEY);
        });

        if (stateIdHolder) {
            persistentStorage.setItem(stateIdHolder.getAttribute(STATE_ID_DATA_KEY), state);
        }
    }

    Collapse.prototype.show = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'expanded'));

        setState(this._triggerArray, true);

        return original.show.call(this);
    };

    Collapse.prototype.hide = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'collapsed'));

        setState(this._triggerArray, false);

        return original.hide.call(this);
    };

    $(document)
        .on(Event.SHOWN, function(event) {
            mediator.trigger('content:shown', $(event.target));
        })
        .on(Event.HIDDEN, function(event) {
            mediator.trigger('content:hidden', $(event.target));
        })
        .on('initLayout', function(event) {
            $(event.target).find('[data-toggle="collapse"]').each(function(index, el) {
                const state = persistentStorage.getItem(el.getAttribute(STATE_ID_DATA_KEY));

                if (state !== null) {
                    $(el.getAttribute('data-target') || el.getAttribute('href')).collapse(
                        JSON.parse(state) ? 'show' : 'hide'
                    );
                }
            });
        });
});
