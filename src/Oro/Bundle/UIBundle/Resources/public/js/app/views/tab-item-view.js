define(function(require) {
    'use strict';

    var TabItemView;
    var _ = require('underscore');
    var $ = require('jquery');
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

        template: _.template('<a  href="#"\n' +
            'id="<%- uniqueId %>" ' +
            'class="'+ config.templateClassName +'<% if(obj.active) { %> active<% } %>"' +
            'role="tab" ' +
            'data-tab-link ' +
            'data-toggle="tab" ' +
            'aria-controls="<% if(obj.controlTabPanel) { %><%- controlTabPanel %><% } else { %><%- id %><% } %>" ' +
            'aria-selected="<% if(obj.active) { %>true<% } else { %>false<% } %>"' +
            '><%- label %></a>'),

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
            this.$el.toggleClass('changed', !!this.model.get('changed'));

            if (this.model.get('active')) {
                var tabPanel = this.model.get('controlTabPanel') || this.model.get('id');
                $('#' + tabPanel).attr('aria-labelledby', this.model.get('uniqueId'));
            }
        },

        onSelect: function() {
            this.model.set('active', true);
            this.model.trigger('select', this.model);
        }
    });

    return TabItemView;
});
