define(function(require) {
    'use strict';

    var ColumnModel;
    var FieldRelatedModel = require('./field-related-model');

    ColumnModel = FieldRelatedModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null
        }
    });

    return ColumnModel;
});
