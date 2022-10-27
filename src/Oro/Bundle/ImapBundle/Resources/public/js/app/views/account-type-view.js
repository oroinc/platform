define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

    const AccountTypeView = BaseView.extend({
        html: '',

        events: {
            'change select[name$="[accountType]"]': 'onChangeAccountType'
        },

        /**
         * @inheritdoc
         */
        constructor: function AccountTypeView(options) {
            AccountTypeView.__super__.constructor.call(this, options);
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
            this.initLayout().done(this._resolveDeferredRender.bind(this));
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
