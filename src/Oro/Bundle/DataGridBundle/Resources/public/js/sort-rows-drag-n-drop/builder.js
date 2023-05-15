import _ from 'underscore';
import SortRowsDragNDropPlugin from 'orodatagrid/js/sort-rows-drag-n-drop/plugin';
import SortRowsDragNDropRow from 'orodatagrid/js/sort-rows-drag-n-drop/row';
import SortRowsDragNDropModel from 'orodatagrid/js/sort-rows-drag-n-drop/model';

import loadModules from 'oroui/js/app/services/load-modules';

export default {
    SORT_ORDER_COLUMN_NAME: 'sortOrder',

    processDatagridOptions(deferred, options) {
        if (_.isMobile()) {
            // sorting via drag n drop is not supported on mobile version
            deferred.resolve();
            return;
        }

        const {sortRowsDragNDropBuilder = {}} = options.gridBuildersOptions;
        const {
            sortOrderColumnName = this.SORT_ORDER_COLUMN_NAME,
            renderDraggableSeparator = false,
            dropZones = {},
            ...pluginOptions
        } = sortRowsDragNDropBuilder;

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

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
            if (renderDraggableSeparator && data.length) {
                // Add fake model for a separator row to grid collection
                data.push({
                    id: SortRowsDragNDropModel.SEPARATOR_ID,
                    [sortOrderColumnName]: Infinity
                });
            }
            return data;
        };

        options.data.data = updateData(options.data.data);
        const {
            parseResponseModels,
            parseResponseOptions
        } = options.metadata.options;
        Object.assign(options.metadata.options, {
            parseResponseModels: function(resp) {
                if (parseResponseModels) {
                    resp = parseResponseModels.call(this, resp);
                }
                return 'data' in resp ? updateData(resp.data) : resp;
            },
            parseResponseOptions: function(resp = {}) {
                if (parseResponseOptions) {
                    resp = parseResponseOptions.call(this, resp);
                }
                const {options = {}} = resp;
                return {
                    comparator: '_sortOrder',
                    sortOrderAttrName: sortOrderColumnName,
                    ...options
                };
            }
        });

        const modulesToLoad = Object.fromEntries(
            Object.entries(dropZones)
                .filter(([, val]) => typeof val === 'string')
        );

        let $rootEl = options.$el;
        if ($rootEl.parents('[role="dialog"]').length) {
            $rootEl = $rootEl.parents('.ui-dialog-content');
        }
        loadModules(modulesToLoad).then(modules => {
            options.metadata.plugins.push({
                constructor: SortRowsDragNDropPlugin,
                options: {
                    $rootEL: $rootEl,
                    ...pluginOptions,
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
