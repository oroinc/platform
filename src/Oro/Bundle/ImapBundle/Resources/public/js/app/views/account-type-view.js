define(function(require) {
    'use strict';

    var accountTypeView;
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    accountTypeView =  BaseView.extend({

        url: '',

        events: {
            'change select[name="oro_user_user_form[imapAccountType][accountType]"]': 'changeHandler'
        },

        html: '',

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

        changeHandler: function(e) {
            this.trigger('imapConnectionChangeType', $(e.target).val());

        },
        setHtml: function(html) {
            this.html = html;

            return this;
        }
    });

    return accountTypeView;
});
