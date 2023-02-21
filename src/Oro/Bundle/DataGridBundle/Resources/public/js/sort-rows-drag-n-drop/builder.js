import SortRowsDragNDropPlugin from 'orodatagrid/js/sort-rows-drag-n-drop/plugin';
import SortRowsDragNDropRow from 'orodatagrid/js/sort-rows-drag-n-drop/row';
import SortRowsDragNDropModel from 'orodatagrid/js/sort-rows-drag-n-drop/model';

import loadModules from 'oroui/js/app/services/load-modules';

export default {
    processDatagridOptions(deferred, options) {
        const {sortRowsDragNDropBuilder = {}} = options.gridBuildersOptions;
        const {
            sortOrderColumnName,
            renderIconColumn: addIconColumn = true,
            dropZones = {}
        } = sortRowsDragNDropBuilder;

        if (sortOrderColumnName === void 0) {
            throw new Error('Options "sortOrderColumnName" is required');
        }

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
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

        const updateData = data => {
            data.push({
                id: SortRowsDragNDropModel.SEPARATOR_ID,
                [sortOrderColumnName]: Infinity
            });
            return data;
        };

        options.data.data = updateData(options.data.data);
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                return 'data' in resp ? updateData(resp.data) : resp;
            }
        });

        const modulesToLoad = {};
        Object.entries(dropZones).forEach(([key, val]) => {
            if (typeof val === 'string') {
                modulesToLoad[key] = val;
                delete dropZones[key];
            }
        });

        let $rootEl = options.$el;
        if ($rootEl.parents('[role="dialog"]').length) {
            $rootEl = $rootEl.parents('.ui-dialog-content');
        }
        loadModules(modulesToLoad).then(modules => {
            options.metadata.plugins.push({
                constructor: SortRowsDragNDropPlugin,
                options: {
                    $rootEL: $rootEl,
                    dropZones: {...dropZones, ...modules}
                }
            });
            deferred.resolve();
        });

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
