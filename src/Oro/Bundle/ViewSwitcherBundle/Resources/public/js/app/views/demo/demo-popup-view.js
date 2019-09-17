define(function(require) {
    'use strict';

    var DemoPopupView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('text!oroviewswitcher/templates/demo-popup.html');

    var COOKIE_KEY = 'demo_popup_hidden';
    var COOKIE_VALUE = '1';

    DemoPopupView = BaseView.extend({
        /**
         * @inheritDoc
         */
        keepElement: false,

        /**
         * @inheritDoc
         */
        autoRender: true,

        className: 'demo-popup',

        /**
         * @inheritDoc
         */
        template: template,

        url: '#',

        visibleClass: 'shown',

        showDelay: 6000,

        /**
         * @inheritDoc
         */
        events: {
            'click [data-role="close"]': 'onClose',
            'transitionend': 'onTransition'
        },

        /**
         * @inheritDoc
         */
        constructor: function DemoPopupView(options) {
            DemoPopupView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (options.url) {
                this.url = options.url;
            }

            this._toDie = false;
            DemoPopupView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Hide popup
         */
        onClose: function() {
            this._toDie = true;
            this.$el.removeClass(this.visibleClass);
            this.setCookie();
        },

        onTransition: function() {
            if (this._toDie && !this.disposed) {
                this.dispose();
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DemoPopupView.__super__.render.apply(this, arguments);

            _.delay(function() {
                this.$el.addClass(this.visibleClass);
            }.bind(this), this.showDelay);
        },

        setCookie: function() {
            if (!navigator.cookieEnabled) {
                return;
            }

            document.cookie = COOKIE_KEY + '=' + COOKIE_VALUE + '; path=/; expires=' + this.getExpiredDate();
        },

        /**
         * @returns {string}
         */
        getExpiredDate: function() {
            var currentDate = new Date().getTime();
            var twoDays = 1000 * 60 * 60 * 48;

            return new Date(currentDate + twoDays).toUTCString();
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = DemoPopupView.__super__.getTemplateData.call(this);

            return _.extend({}, data, {
                url: this.url
            }, this);
        }
    }, {
        /**
         * @static
         * @returns {boolean}
         */
        isApplicable: function() {
            return document.cookie.indexOf(COOKIE_KEY + '=' + COOKIE_VALUE) === -1;
        }
    });

    return DemoPopupView;
});
