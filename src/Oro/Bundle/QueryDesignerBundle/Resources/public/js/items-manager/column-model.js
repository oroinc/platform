define(function(require) {
    'use strict';

    var GroupingModel;
    var FieldRelatedModel = require('./field-related-model');

    GroupingModel = FieldRelatedModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null,
            label: null,
            func: null,
            sorting: null
        }
    });

    return GroupingModel;
});
