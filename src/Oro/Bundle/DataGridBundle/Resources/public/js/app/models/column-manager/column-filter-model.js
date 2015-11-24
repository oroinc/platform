define(function(require) {
    'use strict';

    var ColumnFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    ColumnFilterModel = BaseModel.extend({
        defaults: {
            search: '',
            renderable: false
        },

        filterer: function(item) {
            var search = this.get('search').toLowerCase();
            if (search.length > 0 && item.get('label').toLowerCase().indexOf(search) === -1) {
                return false;
            }
            if (this.get('renderable') && !item.get('renderable')) {
                return false;
            }
            return true;
        }
    });

    return ColumnFilterModel;
});
