define(function(require) {
    'use strict';

    const EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    const ColumnModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null,
            label: null,
            func: null,
            sorting: null
        },

        /**
         * @inheritdoc
         */
        constructor: function ColumnModel(...args) {
            ColumnModel.__super__.constructor.apply(this, args);
        }
    });

    return ColumnModel;
});
