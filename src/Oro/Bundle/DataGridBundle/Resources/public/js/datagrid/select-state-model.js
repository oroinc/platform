define(function(require) {
    'use strict';

    var ColumnFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    ColumnFilterModel = BaseModel.extend({
        defaults: {
            inset: true,
            rows: null
        },

        initialize: function() {
            ColumnFilterModel.__super__.initialize.apply(this, arguments);
            this.attributes.rows = new BaseCollection();
        }
    });

    return ColumnFilterModel;
});
