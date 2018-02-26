define(function(require) {
    'use strict';

    var ColumnModel;
    var EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    ColumnModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null
        },

        /**
         * @inheritDoc
         */
        constructor: function ColumnModel() {
            ColumnModel.__super__.constructor.apply(this, arguments);
        }
    });

    return ColumnModel;
});
