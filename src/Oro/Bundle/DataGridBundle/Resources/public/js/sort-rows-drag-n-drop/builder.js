import SortRowsDragNDropPlugin from 'orodatagrid/js/sort-rows-drag-n-drop/plugin';
import SortRowsDragNDropRow from 'orodatagrid/js/sort-rows-drag-n-drop/row';
import SortRowsDragNDropModel from 'orodatagrid/js/sort-rows-drag-n-drop/model';

export default {
    processDatagridOptions(deferred, options) {
        const {sortRowsDragNDropBuilder = {}} = options.gridBuildersOptions;
        const {
            sortOrderColumnName,
            renderIconColumn: addIconColumn = true
        } = sortRowsDragNDropBuilder;

        if (sortOrderColumnName === void 0) {
            throw new Error('Options "sortOrderColumnName" is required');
        }

        if (addIconColumn) {
            const iconColumn = {
                order: -Infinity,
                editable: false,
                label: '',
                name: 'icon',
                renderable: true,
                type: 'sort-icon',
                manageable: false,
                notMarkAsBlank: true
            };

            options.metadata.columns.push(iconColumn);
        }

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.plugins.push({
            constructor: SortRowsDragNDropPlugin,
            options: {
                $rootEL: options.$el.scrollParent()
            }
        });

        options.themeOptions = {
            ...options.themeOptions,
            rowView: SortRowsDragNDropRow
        };

        if (!options.metadata.options) {
            options.metadata.options = {};
        }

        Object.assign(options.metadata.options, {
            comparator: '_sortOrder',
            sortOrderAttrName: sortOrderColumnName,
            model: SortRowsDragNDropModel
        });

        deferred.resolve();
        return deferred;
    },

    /**
     /**
     * Init() function is required
     */
    init(deferred) {
        return deferred.resolve();
    }
};
