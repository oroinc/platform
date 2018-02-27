define(function(require) {
    'use strict';

    var TabItemView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var module = require('module');
    var config = module.config();

    config = _.extend({
        className: 'nav-item',
        templateClassName: 'nav-link'
    }, config);

    TabItemView = BaseView.extend({
        tagName: 'li',

        className: config.className,

        template: _.template('<a href="#" class="' + config.templateClassName + '" data-tab-link><%- label %></a>'),

        listen: {
            'change:active model': 'updateStates',
            'change:changed model': 'updateStates'
        },

        events: {
            'click [data-tab-link]': 'onSelect'
        },

        /**
         * @inheritDoc
         */
        constructor: function TabItemView() {
            TabItemView.__super__.constructor.apply(this, arguments);
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
            this.model.trigger('select', this.model);
        }
    });

    return TabItemView;
});
