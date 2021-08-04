define(function(require) {
    'use strict';

    const EntityFieldUtil = require('oroentity/js/entity-field-select-util');
    const Select2EntityFieldComponent = require('oro/select2-entity-field-component');

    const Select2EntityFieldSelectComponent = Select2EntityFieldComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function Select2EntityFieldSelectComponent(options) {
            Select2EntityFieldSelectComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.util = new EntityFieldUtil(options._sourceElement);
            Select2EntityFieldSelectComponent.__super__.initialize.call(this, options);
        },

        preConfig: function(config) {
            const that = this;
            Select2EntityFieldSelectComponent.__super__.preConfig.call(this, config);
            config.collapsibleResults = true;
            config.data = function() {
                return {
                    more: false,
                    results: that.util._getData()
                };
            };
            return config;
        }
    });
    return Select2EntityFieldSelectComponent;
});
