define(function(require) {
    'use strict';

    var DatePickerTabsView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    DatePickerTabsView = BaseView.extend({
        autoRender: true,

        events: {
            'click .nav-tabs a': 'onTabSwitch'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['data', 'template']));
            DatePickerTabsView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var data = this.getTemplateData();
            var template = this.getTemplateFunction();
            var html = template(data);
            this.$el.html(html);
        },

        /**
         * @inheritDoc
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
            var visibleTabShown = false;
            _.each(this.data.tabs, function(tab) {
                var visible = !_.isFunction(tab.isVisible) || tab.isVisible();
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
            var method = visible ? 'show' : 'hide';
            this.$('li:has(a.' + tabName + ')')[method]();
        }
    });

    return DatePickerTabsView;
});
