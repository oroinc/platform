define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Select2Component = require('oro/select2-component');

    const Select2EntityFieldComponent = Select2Component.extend({
        util: null,

        /**
         * @inheritdoc
         */
        constructor: function Select2EntityFieldComponent(options) {
            Select2EntityFieldComponent.__super__.constructor.call(this, options);
        },

        preConfig: function(config) {
            const that = this;
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
