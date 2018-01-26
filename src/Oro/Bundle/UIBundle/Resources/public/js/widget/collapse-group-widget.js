define(function(require) {
    'use strict';

    require('jquery-ui');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    $.widget('oroui.collapseGroupWidget', {
        options: {
            group: '',
            openClass: 'expanded'
        },

        _init: function() {
            if (!this.options.group) {
                $.error('oroui.collapseGroupWidget: group option is required');
            }

            this._on({
                click: this._toggle
            });

            mediator.on('collapse-group:' + this.options.group + ':setState', this._setGroupState, this);

            var states = {collapsed: 0, expanded: 0};
            mediator.trigger('collapse-group-widgets:' + this.options.group + ':collectStates', states);
            this._setGroupState(states.collapsed === 0);
        },

        _destroy: function() {
            mediator.off(null, null, this);
            this._super();
        },

        _toggle: function() {
            this._setState(!this.element.hasClass(this.options.openClass));
        },

        _setState: function(state) {
            this._setGroupState(state);
            mediator.trigger('collapse-group-widgets:' + this.options.group + ':setState', state);
        },

        _setGroupState: function(state) {
            this.element.toggleClass(this.options.openClass, state);
        }
    });

    return 'collapseGroupWidget';
});
