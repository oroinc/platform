define(function(require) {
    'use strict';

    var Select2EntityFieldSelectComponent;
    var EntityFieldUtil = require('oroentity/js/entity-field-select-util');
    var Select2EntityFieldComponent = require('oro/select2-entity-field-component');

    Select2EntityFieldSelectComponent = Select2EntityFieldComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function Select2EntityFieldSelectComponent() {
            Select2EntityFieldSelectComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.util = new EntityFieldUtil(options._sourceElement);
            Select2EntityFieldSelectComponent.__super__.initialize.call(this, options);
        },

        preConfig: function(config) {
            var that = this;
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
