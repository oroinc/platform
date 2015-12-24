define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var CheckConnectionView = require('../views/check-connection-view');
    var CheckConnectionModel = require('../models/check-connection-model');
    var CheckConnectionComponent;

    CheckConnectionComponent = BaseComponent.extend({
        /**
         * Initialize component
         *
         * @param {Object} options
         * @param {string} options.elementNamePrototype
         */
        initialize: function(options) {
            if (options.elementNamePrototype) {
                var viewOptions = _.extend({
                    'model': new CheckConnectionModel({}),
                    'el': $(options._sourceElement).closest(options.parentElementSelector),
                    'entity': options.forEntity || 'user',
                    'entityId': options.id,
                    'organization': options.organization || ''
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
