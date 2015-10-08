define(function(require) {
    'use strict';

    var EntityFieldModel;
    var _ = require('underscore');
    var BaseModel = require('oroui/js/app/models/base/model');
    var EntityFieldsUtil = require('oroentity/js/entity-fields-util');

    EntityFieldModel = BaseModel.extend({
        fieldAttribute: 'name',

        /**
         * @type {EntityFieldsUtil}
         */
        entityFieldsUtil: null,

        /**
         * @inheritDoc
         */
        initialize: function(attributes, options) {
            if (!options || !(options.entityFieldsUtil instanceof EntityFieldsUtil)) {
                throw new TypeError('Option "entityFieldsUtil" have to be instance of EntityFieldsUtil');
            }
            _.extend(this, _.pick(options, ['entityFieldsUtil']));
            EntityFieldModel.__super__.initialize.call(this, attributes, options);
        },

        /**
         * @inheritDoc
         */
        validate: function(attrs, options) {
            var error;
            try {
                this.entityFieldsUtil.pathToEntityChain(attrs[this.fieldAttribute]);
            } catch (e) {
                error = e.message;
            }
            return error;
        }
    });

    return EntityFieldModel;
});
