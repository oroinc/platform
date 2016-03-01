define(function(require) {
    'use strict';

    var GroupingModel;
    var EntityFieldModel = require('./entity-field-model');

    GroupingModel = EntityFieldModel.extend({
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
