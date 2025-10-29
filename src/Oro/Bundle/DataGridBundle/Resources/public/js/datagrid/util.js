import GridColumns from './columns';
import HintView from 'orodatagrid/js/app/views/hint-view';

export default {
    createFilteredColumnCollection: function(columns) {
        const filteredColumns = new GridColumns(columns.where({renderable: true}));

        filteredColumns.listenTo(columns, 'change:renderable add remove reset', function() {
            filteredColumns.reset(columns.where({renderable: true}));
        });

        filteredColumns.listenTo(columns, 'sort', function() {
            filteredColumns.sort();
        });

        return filteredColumns;
    },

    headerCellAbbreviateHint: function(cell, options = {}) {
        if (cell.isLabelAbbreviated) {
            cell.$('[data-grid-header-cell-label]').attr('aria-label', cell.column.get('label'));
            cell.$('[data-grid-header-cell-text]').attr('aria-hidden', true);
            cell.subview('hint', new HintView({
                el: cell.$el,
                autoRender: true,
                popoverConfig: {
                    trigger: 'hover focus',
                    delay: {
                        show: 300
                    },
                    content: cell.column.get('label')
                },
                ...options
            }));
        } else {
            // if abbreviation was not created -- add class to make label shorten over styles
            cell.$el.addClass('shortenable-label');
        }

        return cell;
    }
};
