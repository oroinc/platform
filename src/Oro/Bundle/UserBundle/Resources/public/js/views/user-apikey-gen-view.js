define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view'
], function($, _, __, messenger, BaseView) {
    'use strict';

    var UserApiKeyGenView;

    /**
     * @export orouser/js/views/user-apikey-gen-view
     */
    UserApiKeyGenView = BaseView.extend({
        events: {
            'submit form': 'onSubmit'
        },

        options: {
            formSelector: null,
            apiKeyElementSelector: null,
            responseMessage: null
        },

        responseMessage: 'Generate key was successful. New key:{{ new_api_key }}',

        requiredOptions: ['formSelector', 'apiKeyElementSelector'],

        $form: null,

        $apiKeyElement: null,

        $submitBtn: null,

        /**
         * @inheritDoc
         */
        constructor: function UserApiKeyGenView() {
            UserApiKeyGenView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var missingRequiredOptions = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (missingRequiredOptions.length) {
                throw new TypeError('Missing required option(s): ' + missingRequiredOptions.join(','));
            }
            if (this.options.responseMessage !== null) {
                this.responseMessage = this.options.responseMessage;
            }
            this.$form = $(this.options.formSelector);
            this.$apiKeyElement = $(this.options.apiKeyElementSelector);
            this.$submitBtn = this.$form.find('[type=submit]');
            if (this.$submitBtn === undefined) {
                throw new Error('Submit button element is missing!');
            }
        },

        /**
         * onSubmit form event handler
         */
        onSubmit: function(event) {
            var data = this.$form.serializeArray();
            var url = this.$form.attr('action');

            if (!this.$submitBtn.is('.process')) {
                this.$submitBtn.addClass('process');
            }

            var options = {
                type: 'POST',
                data: data,
                dataType: 'json'
            };
            $.ajax(url, options)
                .done(_.bind(this.onAjaxSuccess, this))
                .always(_.bind(this.onAjaxComplete, this));

            event.stopPropagation();
            event.preventDefault();
        },

        /**
         * Ajax success handler
         *
         * @param response
         */
        onAjaxSuccess: function(response) {
            var newApiKey = response.data.apiKey;
            this.$apiKeyElement.html(newApiKey);
            messenger.notificationMessage(
                'success',
                __(this.responseMessage, {new_api_key: ' <strong>' + newApiKey + '</strong>'})
            );
        },

        /**
         * Ajax complete handler
         */
        onAjaxComplete: function() {
            this.$submitBtn.removeClass('process');
        }
    });

    return UserApiKeyGenView;
});
