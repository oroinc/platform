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

        attributes: {
            role: 'presentation'
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

        getTemplateData: function() {
            const data = TabItemView.__super__.getTemplateData.call(this);

            data.templateClassName = config.templateClassName;

            return data;
        },

        updateStates: function() {
            const isActive = this.model.get('active');

            this.$el.toggleClass('changed', !!this.model.get('changed'));

            if (isActive) {
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
