define(function(require) {
    'use strict';

    function titleUpdate(state, el) {
        var attr = 'data-' + state + '-title';

        if (el.hasAttribute(attr)) {
            el.setAttribute('title', el.getAttribute(attr));
        }
    }

    var $ = require('jquery');
    var _ = require('underscore');

    require('bootstrap-collapse');

    var Collapse = $.fn.collapse.Constructor;
    var original = _.pick(Collapse.prototype, 'show', 'hide');

    Collapse.prototype.show = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'expanded'));

        return original.show.apply(this, arguments);
    };

    Collapse.prototype.hide = function() {
        this._triggerArray.forEach(_.partial(titleUpdate, 'collapsed'));

        return original.hide.apply(this, arguments);
    };
});
