define(function(require) {
    'use strict';

    var WidgetPickerFilterModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    WidgetPickerFilterModel = BaseModel.extend({
        defaults: {
            search: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerFilterModel() {
            WidgetPickerFilterModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {WidgetPickerModel} item
         * @returns {boolean} true if item included and false otherwise
         */
        filterer: function(item) {
            var search = this.get('search').toLowerCase();
            var title = item.get('title').toLowerCase();
            var description = item.get('description').toLowerCase();
            if (search.length === 0) {
                return true;
            }
            return title.indexOf(search) !== -1 || description.indexOf(search) !== -1;
        }
    });

    return WidgetPickerFilterModel;
});
