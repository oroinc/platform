define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const DatagridSettingsListFilterModel = BaseModel.extend({
        defaults: {
            search: '',
            renderable: false
        },

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsListFilterModel(...args) {
            DatagridSettingsListFilterModel.__super__.constructor.apply(this, args);
        },

        filterer: function(item) {
            const search = this.get('search').toLowerCase();
            if (search.length > 0 && item.get('label').toLowerCase().indexOf(search) === -1) {
                return false;
            }
            if (this.get('renderable') && !item.get('renderable')) {
                return false;
            }
            return true;
        }
    });

    return DatagridSettingsListFilterModel;
});
