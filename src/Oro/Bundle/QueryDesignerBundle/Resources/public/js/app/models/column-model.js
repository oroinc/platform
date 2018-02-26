define(function(require) {
    'use strict';

    var GroupingModel;
    var EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    GroupingModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null,
            label: null,
            func: null,
            sorting: null
        },

        /**
         * @inheritDoc
         */
        constructor: function GroupingModel() {
            GroupingModel.__super__.constructor.apply(this, arguments);
        }
    });

    return GroupingModel;
});
