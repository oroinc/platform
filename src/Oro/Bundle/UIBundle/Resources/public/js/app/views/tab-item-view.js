define(function(require) {
    'use strict';

    var TabItemView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    TabItemView = BaseView.extend({
        tagName: 'li',

        className: 'nav-item',

        template: _.template('<a href="#" class="nav-link"><%= label %></a>'),

        listen: {
            'change:active model': 'updateStates',
            'change:changed model': 'updateStates'
        },

        events: {
            'click a': 'onSelect'
        },

        initialize: function(options) {
            TabItemView.__super__.initialize.apply(this, arguments);

            this.updateStates();
        },

        updateStates: function() {
            this.$el.toggleClass('active', !!this.model.get('active'));
            this.$el.toggleClass('changed', !!this.model.get('changed'));
        },

        onSelect: function() {
            this.model.set('active', true);
        }
    });

    return TabItemView;
});
