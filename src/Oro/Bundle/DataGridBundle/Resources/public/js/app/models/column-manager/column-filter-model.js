define(function(require) {
    'use strict';

    var ColumnFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    ColumnFilterModel = BaseModel.extend({
        defaults: {
            renderable: false
        }
    });

    return ColumnFilterModel;
});
