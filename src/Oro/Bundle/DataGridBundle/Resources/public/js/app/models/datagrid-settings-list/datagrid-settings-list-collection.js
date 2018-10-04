define(function(require) {
    'use strict';

    var DatagridSettingsListCollection;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    DatagridSettingsListCollection = BaseCollection.extend({
        comparator: 'order',

        /**
         * Min quantity of columns that can not be hidden
         *
         * @type {number}
         */
        minVisibleColumnsQuantity: 1,

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsListCollection() {
            DatagridSettingsListCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, ['minVisibleColumnsQuantity']));

            DatagridSettingsListCollection.__super__.initialize.apply(this, arguments);

            this.on({
                'change:renderable': this.updateVisibilityChange
            });
        },

        /**
         * @inheritDoc
         */
        reset: function() {
            DatagridSettingsListCollection.__super__.reset.apply(this, arguments);

            this.updateVisibilityChange();

            // columns are already properly ordered in a grid, here is just legalized this order
            this.each(function(column, i) {
                column.set('order', i);
            });
        },

        /**
         * Updates columns attribute disabledVisibilityChange
         * (disables/enables show/hide column functionality)
         */
        updateVisibilityChange: function() {
            var visibleColumns = this.where({renderable: true});
            var disable = visibleColumns.length <= this.minVisibleColumnsQuantity;

            this.each(function(column) {
                var state = Boolean(column.get('renderable') && (disable || column.get('required')));
                if (column.get('disabledVisibilityChange') !== state) {
                    column.set('disabledVisibilityChange', state);
                }
            }, this);
        }
    });

    return DatagridSettingsListCollection;
});
