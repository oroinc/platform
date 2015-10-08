define(function(require) {
    'use strict';

    var ColumnModel;
    var EntityFieldModel = require('./entity-field-model');

    ColumnModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null
        }
    });

    return ColumnModel;
});
