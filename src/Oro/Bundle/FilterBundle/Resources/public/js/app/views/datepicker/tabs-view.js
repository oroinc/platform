/*global define*/
define(function (require) {
    'use strict';

    var DatePickerTabsView,
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        BaseView = require('oroui/js/app/views/base/view');
    require('orofilter/js/datevariables-widget');

    DatePickerTabsView = BaseView.extend({
        autoRender: true,
        keepElement: true,

        events: {
            'click .nav-tabs a': 'onTabSwitch'
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['data', 'template']));
            DatePickerTabsView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed && this.$content) {
                this.$content.remove();
            }
            DatePickerTabsView.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function () {
            var data, template, html;
            data = this.getTemplateData();
            template = this.getTemplateFunction();
            html = template(data);
            if (this.$content) {
                this.$content.remove();
            }
            this.$content = this.$el.append(html);
        },

        /**
         * @inheritDoc
         * @returns {*}
         */
        getTemplateData: function () {
            return this.data;
        },

        /**
         * Handles tab switch ivent
         *
         * @param {jQuery.Event} e
         */
        onTabSwitch: function (e) {
            e.preventDefault();
            this.$(e.currentTarget).tab('show');
        },

        /**
         * Opens the tab by its name
         *
         * @param {string} tabName
         */
        show: function (tabName) {
            this.$('[href^="#' + tabName + '-"]').tab('show');
        }
    });

    return DatePickerTabsView;
});
