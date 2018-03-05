define(function(require) {
    'use strict';

    var EmailNotificationFormComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    EmailNotificationFormComponent = BaseComponent.extend(/** @lends EmailNotificationFormComponent.prototype */ {
        options: {
            selectors: {
                form: null,
                listenChangeElements: []
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailNotificationFormComponent() {
            EmailNotificationFormComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options, _.identity) || {}, this.options);

            _.each(this.getListenChangeElements(), _.bind(function(element) {
                element.on('change', $.proxy(this.onChange, this));
            }, this));

            this.getForm().on('submit', $.proxy(this.onSubmit, this));
        },

        /**
         * @param {Event} e
         */
        onSubmit: function(e) {
            e.preventDefault();
            this.submitForm({name: 'formSubmitMarker', value: true});
        },

        onChange: function() {
            this.submitForm();
        },

        /**
         * @param {Object=} extraData
         */
        submitForm: function(extraData) {
            mediator.execute('showLoading');

            var form = this.getForm();
            var data = form.serializeArray();
            if (extraData) {
                data.push(extraData);
            }

            mediator.execute('submitPage', {url: form.attr('action'), type: form.attr('method'), data: $.param(data)});
        },

        /**
         * @returns {jQuery[]|HTMLElement[]}
         */
        getListenChangeElements: function() {
            if (!this.hasOwnProperty('$elements')) {
                this.$elements = _.map(this.options.selectors.listenChangeElements, function(selector) {
                    return $(selector);
                });
            }

            return this.$elements;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getForm: function() {
            if (!this.hasOwnProperty('$form')) {
                this.$form = $(this.options.selectors.form);
            }

            return this.$form;
        }
    });

    return EmailNotificationFormComponent;
});
