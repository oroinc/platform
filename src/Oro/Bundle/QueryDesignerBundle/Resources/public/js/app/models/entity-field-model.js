define(function(require) {
    'use strict';

    var EntityFieldModel;
    var _ = require('underscore');
    var BaseModel = require('oroui/js/app/models/base/model');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    EntityFieldModel = BaseModel.extend({
        fieldAttribute: 'name',

        /**
         * @type {EntityStructureDataProvider}
         */
        dataProvider: null,

        /**
         * @inheritDoc
         */
        constructor: function EntityFieldModel() {
            EntityFieldModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(attributes, options) {
            if (!options || !(options.dataProvider instanceof EntityStructureDataProvider)) {
                throw new TypeError('Option "dataProvider" have to be instance of EntityStructureDataProvider');
            }
            _.extend(this, _.pick(options, 'dataProvider'));
            EntityFieldModel.__super__.initialize.call(this, attributes, options);
        },

        /**
         * @inheritDoc
         */
        validate: function(attrs, options) {
            var error;
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
