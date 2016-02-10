define(function(require) {
    'use strict';

    var accountTypeView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    accountTypeView =  BaseView.extend({

        html: '',

        events: {
            'change select[name$="[accountType]"]': 'onChangeAccountType'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {},

        render: function() {
            this.$el.html(this.html).find('.control-group.switchable-field').hide();
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
