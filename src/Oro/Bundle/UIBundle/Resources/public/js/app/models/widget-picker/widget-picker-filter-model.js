define(function (require) {
   'use strict';

    var WidgetPickerFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    WidgetPickerFilterModel = BaseModel.extend({
        defaults: {
            search: ''
        },

        /**
         *
         * @param {WidgetPickerModel} item
         * @returns {boolean}
         */
        filterer: function(item) {
            var search = this.get('search').toLowerCase();
            if (search.length > 0 && item.get('title').toLowerCase().indexOf(search) === -1 &&
                item.get('description').toLowerCase().indexOf(search) === -1) {
                return false;
            }
            return true;
        }
    });

    return WidgetPickerFilterModel;
});