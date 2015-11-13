define(function(require) {
    'use strict';

    var ColumnFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    ColumnFilterModel = BaseModel.extend({
        defaults: {
            renderable: false
        },

        filterer: function(item) {
            return !this.get('renderable') || item.get('renderable');
        }
    });

    return ColumnFilterModel;
});
