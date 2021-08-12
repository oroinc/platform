define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const CheckConnectionView = require('../views/check-connection-view');
    const CheckConnectionModel = require('../models/check-connection-model');

    const CheckConnectionComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function CheckConnectionComponent(options) {
            CheckConnectionComponent.__super__.constructor.call(this, options);
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
                    model: new CheckConnectionModel({}),
                    el: $(options._sourceElement).closest(options.parentElementSelector),
                    entity: options.forEntity || 'user',
                    entityId: options.id,
                    organization: options.organization || ''
                }, options.viewOptions || {});
                if (/^.+\[\w+]$/i.test(options.elementNamePrototype)) {
                    viewOptions.formPrefix = options.elementNamePrototype.match(/(.+)\[\w+]$/i)[1];
                }
                this.view = new CheckConnectionView(viewOptions);
            } else {
                // unable to initialize
                $(options._sourceElement).remove();
            }
        }
    });
    return CheckConnectionComponent;
});
