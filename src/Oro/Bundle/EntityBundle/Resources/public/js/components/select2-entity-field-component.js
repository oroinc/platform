define(function(require) {
    'use strict';

    var Select2EntityFieldComponent;
    var _ = require('underscore');
    var Select2Component = require('oro/select2-component');

    Select2EntityFieldComponent = Select2Component.extend({
        util: null,

        /**
         * @inheritDoc
         */
        constructor: function Select2EntityFieldComponent() {
            Select2EntityFieldComponent.__super__.constructor.apply(this, arguments);
        },

        preConfig: function(config) {
            var that = this;
            if (this.util === null) {
                throw new TypeError('Field "util" should be initialized in a child class.');
            }
            Select2EntityFieldComponent.__super__.preConfig.call(this, config);
            if (config.entities) {
                that.util.findEntity = function(entity) {
                    return _.findWhere(config.entities, {name: entity});
                };
            }
            config.formatContext = function() {
                return {
                    getFieldData: function(fieldId) {
                        return that.util.getFieldData(fieldId);
                    },
                    splitFieldId: function(fieldId) {
                        return that.util.splitFieldId(fieldId);
                    }
                };
            };

            return config;
        }
    });
    return Select2EntityFieldComponent;
});
