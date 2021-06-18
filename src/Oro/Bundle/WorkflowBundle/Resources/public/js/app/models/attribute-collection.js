define(function(require) {
    'use strict';

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const AttributeModel = require('./attribute-model');

    const AttributeCollection = BaseCollection.extend({
        model: AttributeModel,

        /**
         * @inheritdoc
         */
        constructor: function AttributeCollection(...args) {
            AttributeCollection.__super__.constructor.apply(this, args);
        }
    });

    return AttributeCollection;
});
