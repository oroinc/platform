define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseCollection = require('oroui/js/app/models/base/collection');

    const DatagridSettingsListCollection = BaseCollection.extend({
        comparator: 'order',

        /**
         * Min quantity of columns that can not be hidden
         *
         * @type {number}
         */
        minVisibleColumnsQuantity: 1,

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsListCollection(...args) {
            DatagridSettingsListCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, ['minVisibleColumnsQuantity']));

            DatagridSettingsListCollection.__super__.initialize.call(this, models, options);

            this.on({
                'change:renderable': this.updateVisibilityChange
            });
        },

        /**
         * @inheritdoc
         */
        reset: function(...args) {
            DatagridSettingsListCollection.__super__.reset.apply(this, args);

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
            const visibleColumns = this.where({renderable: true});
            const disable = visibleColumns.length <= this.minVisibleColumnsQuantity;

            this.each(function(column) {
                const state = Boolean(column.get('renderable') && (disable || column.get('required')));
                if (column.get('disabledVisibilityChange') !== state) {
                    column.set('disabledVisibilityChange', state);
                }
            }, this);
        }
    });

    return DatagridSettingsListCollection;
});
