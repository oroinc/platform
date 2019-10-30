define(function(require) {
    'use strict';

    var DeviceInnerPageView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl-loader!oroviewswitcher/templates/switcher-inner-page.html');

    DeviceInnerPageView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function DeviceInnerPageView(options) {
            DeviceInnerPageView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.data.showSwitcher = navigator.cookieEnabled && !window.frameElement;

            if (!this.data.showSwitcher) {
                this.$el.addClass('closed-head-panel');
            }

            DeviceInnerPageView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = DeviceInnerPageView.__super__.getTemplateData.apply(this, arguments);

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
            var viewName = this.getActiveView();
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
            var viewName = this.$(e.currentTarget).data('view-name');
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
            var actionName = $(event.currentTarget).data('click-action');
            mediator.trigger('demo-page-action:' + actionName, event);
        }
    });

    return DeviceInnerPageView;
});
