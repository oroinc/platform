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
            'shown.bs.tab': 'onTabShown',
            'click': 'onTabClick'
        },

        /**
         * @inheritdoc
         */
        constructor: function TabItemView(options) {
            TabItemView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            TabItemView.__super__.initialize.call(this, options);

            this.updateStates();
        },

        updateStates: function() {
            this.$el.toggleClass('changed', !!this.model.get('changed'));
            const $tab = this.$('[role="tab"]');
            if ($tab.attr('aria-selected') !== String(this.model.get('active'))) {
                $tab.attr('aria-selected', this.model.get('active'));
            }
            if (this.model.get('active')) {
                const tabPanel = this.model.get('controlTabPanel') || this.model.get('id');
                $('#' + tabPanel).attr('aria-labelledby', this.model.get('uniqueId'));
            }
        },

        onTabShown: function(e) {
            this.model.set('active', true);
            this.model.trigger('select', this.model);
        },

        onTabClick: function(e) {
            this.model.trigger('click', this.model);
        }
    });

    return TabItemView;
});
