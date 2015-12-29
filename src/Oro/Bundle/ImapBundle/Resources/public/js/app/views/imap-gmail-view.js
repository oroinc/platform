define(function(require) {
    'use strict';

    var accountTypeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    accountTypeView =  BaseView.extend({
        events: {
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][check]"]': 'onClickConnect',
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][checkFolder]"]': 'onCheckFolder'
        },

        html: '',

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {

        },

        render: function() {
            console.log(this.$el);
            this.$el.html(this.html);
            this._deferredRender();
            this.initLayout().done(_.bind(this._resolveDeferredRender, this));
        },

        setHtml: function(html) {
            this.html = html;
        },

        onClickConnect: function(e) {
            // todo: get token
            this.trigger('imapGmailConnectionSetToken', {type: "Gmail", token:"1111"});
        },

        onCheckFolder: function() {
            this.trigger('imapGmailConnectionGetFolders', {type: "Gmail", token:"1111"});
        }
    });

    return accountTypeView;
});
