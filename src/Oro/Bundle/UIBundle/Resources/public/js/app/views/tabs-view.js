define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const DatePickerTabsView = BaseView.extend({
        autoRender: true,

        events: {
            'click .nav-tabs a': 'onTabSwitch'
        },

        /**
         * @inheritdoc
         */
        constructor: function DatePickerTabsView(options) {
            DatePickerTabsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['data', 'template']));
            DatePickerTabsView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const data = this.getTemplateData();
            const template = this.getTemplateFunction();
            const html = template(data);
            this.$el.html(html);
        },

        /**
         * @inheritdoc
         * @returns {*}
         */
        getTemplateData: function() {
            return this.data;
        },

        /**
         * Handles tab switch event
         *
         * @param {jQuery.Event} e
         */
        onTabSwitch: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.$(e.currentTarget).tab('show');
        },

        /**
         * Opens the tab by its name
         *
         * @param {string} tabName
         */
        show: function(tabName) {
            this.$('[href^="#' + tabName + '-"]').tab('show');
        },

        updateTabsVisibility: function() {
            let visibleTabShown = false;
            _.each(this.data.tabs, function(tab) {
                const visible = !_.isFunction(tab.isVisible) || tab.isVisible();
                this.setTabVisibility(tab.name, visible);

                if (visible && !visibleTabShown) {
                    this.show(tab.name);
                    visibleTabShown = true;
                }
            }, this);
        },

        /**
         * @param {String} tabName
         * @param {Boolean} visible
         */
        setTabVisibility: function(tabName, visible) {
            const method = visible ? 'show' : 'hide';
            this.$('li:has(a.' + tabName + ')')[method]();
        }
    });

    return DatePickerTabsView;
});
