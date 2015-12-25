define(function(require) {
    'use strict';

    var accountTypeView;
    //var $ = require('jquery');
    //var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    //var accountTypeView = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    accountTypeView =  BaseView.extend({

        url: '',

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
            //this.url = options.url;
            //console.log(options);
            //this.select2Config = _.result(options, 'select2Config') || _.extend({}, this.select2Config);

            //$('form[name="oro_user_user_form"]').on(
            //    'change',
            //    'select[name="oro_user_user_form[imapAccountType][accountType]"]',
            //    _.bind(processChange, self)
            //);
        },

        render: function() {
            //this.$el.html(this.html);
            this._deferredRender();
            this.initLayout().done(_.bind(this._resolveDeferredRender, this));
        },

        onClickConnect: function(e) {
            // todo: get token
            mediator.trigger('imapGmailConnectionSetToken', {type: "Gmail", token:"1111"});
        },

        onCheckFolder: function() {

        }
    });

    return accountTypeView;
});
