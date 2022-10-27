define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('text-loader!oroviewswitcher/templates/demo-popup.html');

    const COOKIE_KEY = 'demo_popup_hidden';
    const COOKIE_VALUE = '1';

    const DemoPopupView = BaseView.extend({
        /**
         * @inheritdoc
         */
        keepElement: false,

        /**
         * @inheritdoc
         */
        autoRender: true,

        className: 'demo-popup',

        /**
         * @inheritdoc
         */
        template: template,

        url: '#',

        visibleClass: 'shown',

        showDelay: 6000,

        /**
         * @inheritdoc
         */
        events: {
            'click [data-role="close"]': 'onClose',
            'transitionend': 'onTransition'
        },

        /**
         * @inheritdoc
         */
        constructor: function DemoPopupView(options) {
            DemoPopupView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (options.url) {
                this.url = options.url;
            }

            this._toDie = false;
            DemoPopupView.__super__.initialize.call(this, options);
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
         * @inheritdoc
         */
        render: function() {
            DemoPopupView.__super__.render.call(this);

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
            const currentDate = new Date().getTime();
            const twoDays = 1000 * 60 * 60 * 48;

            return new Date(currentDate + twoDays).toUTCString();
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = DemoPopupView.__super__.getTemplateData.call(this);

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
