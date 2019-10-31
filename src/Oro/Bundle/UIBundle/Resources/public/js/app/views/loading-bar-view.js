define(function(require) {
    'use strict';

    /**
     * This component display line loader when page is loading and ajax request sending
     */
    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');

    const LoadingBarView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'ajaxLoading', 'pageLoading'
        ]),

        /**
         * @property {string}
         */
        className: 'loading-bar',

        /**
         * @property {string}
         */
        container: 'body',

        /**
         * @property {Boolean}
         */
        ajaxLoading: false,

        /**
         * @property {Boolean}
         */
        pageLoading: false,

        /**
         * @property {Boolean}
         */
        active: false,

        /**
         * @inheritDoc
         */
        constructor: function LoadingBarView(options) {
            LoadingBarView.__super__.constructor.call(this, options);
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
            const self = this;

            if (this.pageLoading) {
                $(document).on('ready' + this.eventNamespace(), function() {
                    self.showLoader();
                });

                $(window).on('load' + this.eventNamespace(), function() {
                    self.hideLoader();
                });
            }

            if (this.ajaxLoading) {
                $(document).on('ajaxStart' + this.eventNamespace(), function() {
                    self.showLoader();
                });

                $(document).on('ajaxComplete' + this.eventNamespace(), function() {
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

            const loaderWidth = this.$el.width();

            this.$el.width(loaderWidth).css({animation: 'none'}).width('100%');
            this.$el.delay(200).fadeOut(300, _.bind(function() {
                this.$el.css({
                    width: '',
                    animation: ''
                });
            }, this));
            this.active = false;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            $(document).off(this.eventNamespace());
            $(window).off(this.eventNamespace());

            LoadingBarView.__super__.dispose.call(this);
        }
    });

    return LoadingBarView;
});
