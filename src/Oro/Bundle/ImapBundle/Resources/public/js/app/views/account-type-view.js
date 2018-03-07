define(function(require) {
    'use strict';

    var AccountTypeView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    AccountTypeView = BaseView.extend({
        html: '',

        events: {
            'change select[name$="[accountType]"]': 'onChangeAccountType'
        },

        /**
         * @inheritDoc
         */
        constructor: function AccountTypeView() {
            AccountTypeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {},

        render: function() {
            if (this.html.length) {
                this.$el.html(this.html).find('.control-group.switchable-field').hide();
                this.html = '';
            }

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
         * @returns {AccountTypeView}
         */
        setHtml: function(html) {
            this.html = html;

            return this;
        }
    });

    return AccountTypeView;
});
