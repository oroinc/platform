define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseModel = require('oroui/js/app/models/base/model');
    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    const EntityFieldModel = BaseModel.extend({
        fieldAttribute: 'name',

        /**
         * @type {EntityStructureDataProvider}
         */
        dataProvider: null,

        /**
         * @inheritdoc
         */
        constructor: function EntityFieldModel(...args) {
            EntityFieldModel.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(attributes, options) {
            if (!options || !(options.dataProvider instanceof EntityStructureDataProvider)) {
                throw new TypeError('Option "dataProvider" have to be instance of EntityStructureDataProvider');
            }
            _.extend(this, _.pick(options, 'dataProvider'));
            EntityFieldModel.__super__.initialize.call(this, attributes, options);
        },

        /**
         * @inheritdoc
         */
        validate: function(attrs, options) {
            let error;
            try {
                this.dataProvider.pathToEntityChain(attrs[this.fieldAttribute]);
            } catch (e) {
                error = e.message;
            }
            return error;
        }
    });

    return EntityFieldModel;
});
