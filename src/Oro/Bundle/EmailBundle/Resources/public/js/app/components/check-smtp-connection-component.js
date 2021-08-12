define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const CheckSmtpConnectionView = require('../views/check-smtp-connection-view');
    const CheckSavedSmtpConnectionView = require('../views/check-saved-smtp-connection-view');
    const CheckSmtpConnectionModel = require('../models/check-smtp-connection-model');

    const CheckSmtpConnectionComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function CheckSmtpConnectionComponent(options) {
            CheckSmtpConnectionComponent.__super__.constructor.call(this, options);
        },

        /**
         * Initialize component
         *
         * @param {Object} options
         * @param {string} options.elementNamePrototype
         */
        initialize: function(options) {
            if (options.elementNamePrototype) {
                const viewOptions = _.extend({
                    el: $(options._sourceElement).closest(options.parentElementSelector),
                    entity: options.forEntity || 'user',
                    entityId: options.id,
                    organization: options.organization || ''
                }, options.viewOptions || {});

                if (options.view !== 'saved') {
                    viewOptions.model = new CheckSmtpConnectionModel({});
                    this.view = new CheckSmtpConnectionView(viewOptions);
                } else {
                    this.view = new CheckSavedSmtpConnectionView(viewOptions);
                }
            } else {
                // unable to initialize
                $(options._sourceElement).remove();
            }
        }
    });
    return CheckSmtpConnectionComponent;
});
