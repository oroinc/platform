define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const WidgetPickerFilterModel = BaseModel.extend({
        defaults: {
            search: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerFilterModel(attrs, options) {
            WidgetPickerFilterModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @param {WidgetPickerModel} item
         * @returns {boolean} true if item included and false otherwise
         */
        filterer: function(item) {
            const search = this.get('search').toLowerCase();
            const title = item.get('title').toLowerCase();
            const description = item.get('description').toLowerCase();
            if (search.length === 0) {
                return true;
            }
            return title.indexOf(search) !== -1 || description.indexOf(search) !== -1;
        }
    });

    return WidgetPickerFilterModel;
});
