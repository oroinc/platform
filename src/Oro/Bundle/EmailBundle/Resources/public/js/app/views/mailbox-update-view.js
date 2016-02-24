/*jslint nomen:true*/
/*global define*/
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
    return Backbone.View.extend({
        /**
         * @const
         */
        RELOAD_MARKER: '_reloadForm',

        events: {
            'change [name*="processType"]': 'changeHandler'
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var passwordHolderField = $('input[name="oro_email_mailbox[passwordHolder]"]');
            var passwordField = $('input[name="oro_email_mailbox[origin][password]"]');
            passwordField.val(passwordHolderField.val());

            this.listenTo(mediator, 'change:systemMailBox:email', _.bind(this.onChangeEmail, this));
        },

        changeHandler: function(event) {
            mediator.trigger('serializeFolderCollection');
            var data = this.$el.serializeArray();
            var url = this.$el.attr('action');
            var method = this.$el.attr('method');

            data.push({name: this.RELOAD_MARKER, value: true});
            mediator.execute('submitPage', {url: url, type: method, data: $.param(data)});
        },

        onChangeEmail: function(data) {
            var $oroEmailMailBoxEmail = this.$el.find('input[name="oro_email_mailbox[email]"]');
            if (data && data.email) {
                $oroEmailMailBoxEmail.val(data.email);
                $oroEmailMailBoxEmail.attr('readonly', 'readonly');
            } else {
                $oroEmailMailBoxEmail.removeAttr('readonly');
            }
        }
    });
});
