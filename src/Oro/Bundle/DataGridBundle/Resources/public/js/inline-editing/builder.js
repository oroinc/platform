define(function(require) {
    'use strict';

    var $ = require('jquery');
    var tools = require('oroui/js/tools');

    var gridViewsBuilder = {
        /**
         * Prepares and preloads all required files for inline editing plugin
         *
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         * @param {jQuery} [options.$el] container for the grid
         * @param {string} [options.gridName] grid name
         * @param {Object} [options.gridPromise] grid builder's promise
         * @param {Object} [options.data] data for grid's collection
         * @param {Object} [options.metadata] configuration for the grid
         */
        init: function(deferred, options) {
            if (tools.isMobile() || !options.metadata.inline_editing || !options.metadata.inline_editing.enabled) {
                deferred.resolve();
                return;
            }
            var loadMap = this.getLoadMap(options);
            tools.loadModules(loadMap, function(loaded) {
                options.gridPromise.done(function(grid) {
                    options.metadata.inline_editing.default_editors = loaded.default_editors;
                    options.metadata.inline_editing.cell_editor.component = loaded.cell_editor_component;
                    options.metadata.inline_editing.save_api_accessor.class = loaded.save_api_accessor_class;
                    var columnsMeta = options.metadata.columns;

                    for (var i = 0; i < columnsMeta.length; i++) {
                        var columnMeta = columnsMeta[i];
                        if (columnMeta.editor) {
                            if (columnMeta.editor.component) {
                                columnMeta.editor.component = loaded[columnMeta.name + 'Component'];
                            }
                            if (columnMeta.editor.view) {
                                columnMeta.editor.view = loadMap[columnMeta.name + 'View'];
                            }
                        }
                        if (columnMeta.save_api_accessor && columnMeta.save_api_accessor['class']) {
                            columnMeta.save_api_accessor['class'] = loaded[columnMeta.name + 'AccessorClass'];
                        }
                    }

                    grid.pluginManager.create(loaded.plugin, options);
                    grid.pluginManager.enable(loaded.plugin);
                    deferred.resolve();
                });
            });
        },

        getDefaultOptions: function() {
            return {
                plugin: 'orodatagrid/js/app/plugins/grid/inline-editing-plugin',
                default_editors: 'orodatagrid/js/inline-editing/default-editors',
                behaviour: 'enable_all',
                cell_editor: {
                    component: 'orodatagrid/js/app/components/cell-popup-editor-component'
                },
                save_api_accessor: {
                    'class': 'oroui/js/tools/api-accessor',
                    http_method: 'PATCH'
                }
            };
        },

        getLoadMap: function(options) {
            var loadMap = {};
            // plugin
            var mainConfig = {};
            $.extend(true, mainConfig, this.getDefaultOptions(), options.metadata.inline_editing);
            options.metadata.inline_editing = mainConfig;
            loadMap.plugin = mainConfig.plugin;
            loadMap.default_editors = mainConfig.default_editors;
            loadMap.cell_editor_component = mainConfig.cell_editor.component;
            loadMap.save_api_accessor_class = mainConfig.save_api_accessor['class'];
            // column views and components
            var columnsMeta = options.metadata.columns;
            for (var i = 0; i < columnsMeta.length; i++) {
                var columnMeta = columnsMeta[i];
                if (columnMeta.editor) {
                    if (columnMeta.editor.component) {
                        loadMap[columnMeta.name + 'Component'] = columnMeta.editor.component;
                    }
                    if (columnMeta.editor.view) {
                        loadMap[columnMeta.name + 'View'] = columnMeta.editor.view;
                    }
                }
                if (columnMeta.save_api_accessor && columnMeta.save_api_accessor['class']) {
                    loadMap[columnMeta.name + 'AccessorClass'] = columnMeta.save_api_accessor['class'];
                }
            }

            return loadMap;
        }
    };

    return gridViewsBuilder;
});
