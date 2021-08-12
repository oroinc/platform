define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, Backbone, _, __, mediator) {
    'use strict';

    /**
     * @extends Backbone.View
     */
    const MailboxUpdateView = Backbone.View.extend({
        /**
         * @const
         */
        RELOAD_MARKER: '_reloadForm',

        events: {
            'change [name*="processType"]': 'changeHandler'
        },

        /**
         * @inheritdoc
         */
        constructor: function MailboxUpdateView(options) {
            MailboxUpdateView.__super__.constructor.call(this, options);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const passwordHolderField = $('input[name="oro_email_mailbox[passwordHolder]"]');
            const passwordField = $('input[name="oro_email_mailbox[origin][password]"]');
            passwordField.val(passwordHolderField.val());

            this.listenTo(mediator, 'change:systemMailBox:email', this.onChangeEmail.bind(this));
        },

        changeHandler: function(event) {
            mediator.trigger('serializeFolderCollection');
            const data = this.$el.serializeArray();
            const url = this.$el.attr('action');
            const method = this.$el.attr('method');

            data.push({name: this.RELOAD_MARKER, value: true});
            mediator.execute('submitPage', {url: url, type: method, data: $.param(data)});
        },

        onChangeEmail: function(data) {
            const $oroEmailMailBoxEmail = this.$el.find('input[name="oro_email_mailbox[email]"]');
            if (data && data.email) {
                $oroEmailMailBoxEmail.val(data.email);
                $oroEmailMailBoxEmail.prop('readonly', 'readonly');
            } else {
                $oroEmailMailBoxEmail.prop('readonly', false);
            }
        }
    });

    return MailboxUpdateView;
});
