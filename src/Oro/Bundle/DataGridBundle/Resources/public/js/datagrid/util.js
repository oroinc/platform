define(['./columns', 'chaplin'], function(GridColumns, Chaplin) {
    'use strict';

    return {
        createFilteredColumnCollection: function(columns) {
            var filteredColumns = new GridColumns(columns.where({renderable: true}));

            filteredColumns.listenTo(columns, 'change:renderable add remove reset', function() {
                filteredColumns.reset(columns.where({renderable: true}));
            });

            filteredColumns.listenTo(columns, 'sort', function() {
                filteredColumns.sort();
            });

            return filteredColumns;
        },

        /**
         * Cells is not removed from DOM by Chaplin.CollectionView or their realizations
         * This function can be used as replacement for removeSubview() method
         */
        removeSubview: function(nameOrView) {
            if (!nameOrView) {
                return;
            }
            var byName = this.subviewsByName;
            var view;
            if (typeof nameOrView === 'string') {
                view = byName[nameOrView];
            } else {
                view = nameOrView;
            }
            if (!view) {
                return;
            }
            var $viewEl = view.$el;
            Chaplin.CollectionView.prototype.removeSubview.call(this, nameOrView);
            $viewEl.remove();
        }
    };
});
