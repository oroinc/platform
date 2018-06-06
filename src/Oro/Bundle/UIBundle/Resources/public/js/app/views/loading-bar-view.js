define(function(require) {
    'use strict';

    /**
     * This component display line loader when page is loading and ajax request sending
     */
    var LoadingBarView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');

    LoadingBarView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'ajaxLoading', 'pageLoading'
        ]),

        /** @property {string} */
        className: 'loading-bar',

        /** @property {string} */
        container: 'body',

        /**
         * @property {Object}
         */
        ajaxLoading: false,

        /**
         * @property {Object}
         */
        pageLoading: false,

        /**
         * @property {Boolean}
         */
        active: false,

        /**
         * @inheritDoc
         */
        constructor: function LoadingBarView() {
            LoadingBarView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         */
        initialize: function() {
            this.bindEvents(this);
        },

        /**
         * Bind ajaxStart, ajaxComplete, ready and load listeners
         */
        bindEvents: function() {
            var self = this;

            if (this.pageLoading) {
                $(document).ready(function() {
                    self.showLoader();
                });

                $(window).on('load', function() {
                    self.hideLoader();
                });
            }

            if (this.ajaxLoading) {
                $(document).ajaxStart(function() {
                    self.showLoader();
                });

                $(document).ajaxComplete(function() {
                    self.hideLoader();
                });
            }
        },

        showLoader: function() {
            if (this.active) {
                return;
            }

            this.$el.show();
            this.active = true;
        },

        hideLoader: function() {
            if (!this.active) {
                return;
            }

            var loaderWidth = this.$el.width();

            this.$el.width(loaderWidth).css({animation: 'none'}).width('100%');
            this.$el.delay(200).fadeOut(300, _.bind(function() {
                this.$el.css({
                    width: '',
                    animation: ''
                });
            }, this));
            this.active = false;
        }
    });

    return LoadingBarView;
});
