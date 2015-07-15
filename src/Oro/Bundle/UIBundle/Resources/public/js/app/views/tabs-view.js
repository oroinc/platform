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
            this.$(e.currentTarget).tab('show');
        },

        /**
         * Opens the tab by its name
         *
         * @param {string} tabName
         */
        show: function(tabName) {
            this.$('[href^="#' + tabName + '-"]').tab('show');
        }
    });

    return DatePickerTabsView;
});
