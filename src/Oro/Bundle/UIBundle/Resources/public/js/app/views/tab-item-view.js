define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');
    let config = require('module-config').default(module.id);

    config = _.extend({
        className: 'nav-item',
        templateClassName: 'nav-link'
    }, config);

    const TabItemView = BaseView.extend({
        tagName: 'li',

        className: config.className,

        template: require('tpl-loader!oroui/templates/tab-collection-item.html'),

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
        constructor: function TabItemView(options) {
            TabItemView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            TabItemView.__super__.initialize.call(this, options);

            this.updateStates();
        },

        updateStates: function() {
            this.$('a').toggleClass('active', !!this.model.get('active'));
            this.$el.toggleClass('changed', !!this.model.get('changed'));

            if (this.model.get('active')) {
                const tabPanel = this.model.get('controlTabPanel') || this.model.get('id');
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
