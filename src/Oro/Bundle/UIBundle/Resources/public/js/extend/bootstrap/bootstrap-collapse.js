define(function(require) {
    'use strict';

    function titleUpdate(state, el) {
        var attr = 'data-' + state + '-title';

        if (el.hasAttribute(attr)) {
            el.setAttribute('title', el.getAttribute(attr));
        }
    }

    function setState(triggerArray, state) {
        var stateIdHolder = _.find(triggerArray, function(el) {
            return el.hasAttribute(STATE_ID_DATA_KEY);
        });

        if (stateIdHolder) {
            persistentStorage.setItem(stateIdHolder.getAttribute(STATE_ID_DATA_KEY), state);
        }
    }

    var $ = require('jquery');
    var _ = require('underscore');
    var persistentStorage = require('oroui/js/persistent-storage');

    require('bootstrap-collapse');

    var STATE_ID_DATA_KEY = 'data-collapse-state-id';
    var Collapse = $.fn.collapse.Constructor;
    var original = _.pick(Collapse.prototype, 'show', 'hide');

    Collapse.prototype.show = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'expanded'));

        setState(this._triggerArray, true);

        return original.show.apply(this, arguments);
    };

    Collapse.prototype.hide = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'collapsed'));

        setState(this._triggerArray, false);

        return original.hide.apply(this, arguments);
    };

    $(document)
        .on('initLayout', function(event) {
            $(event.target).find('[data-toggle="collapse"]').each(function(index, el) {
                var state = persistentStorage.getItem(el.getAttribute(STATE_ID_DATA_KEY));

                if (state !== null) {
                    $(el.getAttribute('data-target') || el.getAttribute('href')).collapse(
                        JSON.parse(state) ? 'show' : 'hide'
                    );
                }
            });
        });
});
