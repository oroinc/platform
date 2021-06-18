define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    const EntityFieldsCollection = BaseCollection.extend({
        /**
         * @type {EntityStructureDataProvider}
         */
        dataProvider: null,

        /**
         * @inheritdoc
         */
        constructor: function EntityFieldsCollection(...args) {
            EntityFieldsCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            if (!options || !(options.dataProvider instanceof EntityStructureDataProvider)) {
                throw new TypeError('Option "dataProvider" have to be instance of EntityStructureDataProvider');
            }
            _.extend(this, _.pick(options, 'dataProvider'));
            EntityFieldsCollection.__super__.initialize.call(this, models, options);
        },

        /**
         * @inheritdoc
         */
        _prepareModel: function(attrs, options) {
            options.dataProvider = this.dataProvider;
            return EntityFieldsCollection.__super__._prepareModel.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        clone: function() {
            return new this.constructor(this.models, {dataProvider: this.dataProvider});
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
         * @return {EntityFieldsCollection}
         */
        removeInvalidModels: function() {
            const models = this.filter(function(model) {
                return !model.isValid();
            });
            this.remove(models);
            return this;
        }
    });

    return EntityFieldsCollection;
});
