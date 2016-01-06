define(function(require) {
    'use strict';

    var accountTypeView;
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    accountTypeView =  BaseView.extend({

        html: '',

        events: {
            'change select[name$="[accountType]"]': 'onChangeAccountType',
            'click button[name$="[disconnect]"]': 'onClickDisconnect'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {},

        render: function() {
            this.$el.html(this.html);
            this._deferredRender();
            this.initLayout().done(_.bind(this._resolveDeferredRender, this));
        },

        /**
         * handler event change AccountType
         * @param e
         */
        onChangeAccountType: function(e) {
            this.trigger('imapConnectionChangeType', $(e.target).val());
        },

        /**
         * handler event click button Disconnect
         */
        onClickDisconnect: function() {
            this.trigger('imapConnectionDisconnect');
        },

        /**
         * Set property html
         *
         * @param html
         *
         * @returns {accountTypeView}
         */
        setHtml: function(html) {
            this.html = html;

            return this;
        }
    });

    return accountTypeView;
});
