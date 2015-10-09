define(function(require) {
    'use strict';

    var FieldsCollection;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var EntityFieldsUtil = require('oroentity/js/entity-fields-util');

    FieldsCollection = BaseCollection.extend({
        /**
         * @type {EntityFieldsUtil}
         */
        entityFieldsUtil: null,

        /**
         * @inheritDoc
         */
        initialize: function(models, options) {
            if (!options || !(options.entityFieldsUtil instanceof EntityFieldsUtil)) {
                throw new TypeError('Option "entityFieldsUtil" have to be instance of EntityFieldsUtil');
            }
            _.extend(this, _.pick(options, ['entityFieldsUtil']));
            FieldsCollection.__super__.initialize.call(this, models, options);
        },

        /**
         * @inheritDoc
         */
        _prepareModel: function(attrs, options) {
            options.entityFieldsUtil = this.entityFieldsUtil;
            return FieldsCollection.__super__._prepareModel.call(this, attrs, options);
        },

        /**
         * @inheritDoc
         */
        clone: function() {
            return new this.constructor(this.models, {entityFieldsUtil: this.entityFieldsUtil});
        },

        /**
         * Check if all models in collection are valid
         *  - check if models contain valid field reference
         *
         * @return {boolean}
         */
        isValid: function() {
            return this.every(function(model) {
                return model.isValid();
            });
        },

        /**
         * Removes invalid models from collection
         *
         * @return {FieldsCollection}
         */
        removeInvalidModels: function() {
            var models = this.filter(function(model) {
                return !model.isValid();
            });
            this.remove(models);
            return this;
        }
    });

    return FieldsCollection;
});
