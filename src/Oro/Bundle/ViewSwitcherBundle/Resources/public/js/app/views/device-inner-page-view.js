define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!oroviewswitcher/templates/switcher-inner-page.html');

    const DeviceInnerPageView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'data'
        ]),

        keepElement: true,

        autoRender: true,

        template: template,

        events: {
            'click .view-switcher .view-switcher__item[data-view-name]': 'onViewSwitchClick',
            'click .head-panel__trigger-wrapper': 'onHeadPanelSwitch',
            'click [data-click-action]': 'proxyClickEvent'
        },

        defaultActive: 'desktop',

        /**
         * @inheritdoc
         */
        constructor: function DeviceInnerPageView(options) {
            DeviceInnerPageView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.data.showSwitcher = navigator.cookieEnabled && !window.frameElement;

            if (!this.data.showSwitcher) {
                this.$el.addClass('closed-head-panel');
            }

            DeviceInnerPageView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = DeviceInnerPageView.__super__.getTemplateData.call(this);

            return _.extend({}, data, this.data);
        },

        /**
         * Updates url in data object
         *
         * @param {string} url
         */
        setUrl: function(url) {
            this.data.url = url;
        },

        /**
         * Fetches url from data object
         */
        getUrl: function() {
            return this.data.url;
        },

        /**
         * Updates
         *  - active view name in data object
         *  - HTML of the view
         *
         * @param {string} viewName
         */
        setActiveView: function(viewName) {
            this.data.activeView = this.data.showSwitcher ? viewName : this.defaultActive;
            this.updateMarkup();
        },

        /**
         * Fetches active view name from data object
         */
        getActiveView: function() {
            return this.data.activeView;
        },

        /**
         * Updates HTML of the view
         */
        updateMarkup: function() {
            const viewName = this.getActiveView();
            this.$('.view-switcher__item').removeClass('active');
            this.$('.view-switcher .' + viewName).addClass('active');
            this.$('.content-area__wrapper').attr('data-view-name', viewName);
        },

        /**
         * Handles view switch
         *
         * @param {jQuery.Event} e
         */
        onViewSwitchClick: function(e) {
            const viewName = this.$(e.currentTarget).data('view-name');
            e.preventDefault();
            if (viewName === this.getActiveView()) {
                return;
            }
            this.trigger('view-switch', viewName);
        },

        /**
         * Handles head panel switch
         */
        onHeadPanelSwitch: function() {
            this.$el.toggleClass('closed-head-panel');
        },

        /**
         * Proxy DOM event into mediator
         * @param event
         */
        proxyClickEvent: function(event) {
            const actionName = $(event.currentTarget).data('click-action');
            mediator.trigger('demo-page-action:' + actionName, event);
        }
    });

    return DeviceInnerPageView;
});
