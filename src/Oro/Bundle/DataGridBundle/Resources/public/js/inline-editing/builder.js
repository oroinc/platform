define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var InlineEditingHelpPlugin = require('../app/plugins/grid/inline-editing-help-plugin');
    var console = window.console;

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
            if (tools.isMobile() || !options.metadata.inline_editing || !options.metadata.inline_editing.enable) {
                deferred.resolve();
                return;
            }
            var promises = this.preparePlugin(options)
                .concat(this.prepareColumns(options));

            $.when.apply($, promises).done(function() {
                options.gridPromise.done(function(grid) {
                    grid.pluginManager.create(options.metadata.inline_editing.plugin, options);
                    grid.pluginManager.enable(options.metadata.inline_editing.plugin);
                    if (options.metadata.inline_editing.disable_help !== false) {
                        grid.pluginManager.enable(InlineEditingHelpPlugin);
                    }
                    deferred.resolve();
                });
            }).fail(function(e) {
                if (console && console.error) {
                    console.log(e);
                    console.error('Inline editing loading failed. Reason: ' + e.message);
                } else {
                    throw e;
                }
                deferred.resolve();
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

        preparePlugin: function(options) {
            var promises = [];
            var mainConfig = {};
            $.extend(true, mainConfig, this.getDefaultOptions(), options.metadata.inline_editing);
            options.metadata.inline_editing = mainConfig;
            promises.push(tools.loadModuleAndReplace(mainConfig, 'plugin'));
            promises.push(tools.loadModuleAndReplace(mainConfig, 'default_editors'));
            promises.push(tools.loadModuleAndReplace(mainConfig.cell_editor, 'component'));
            promises.push(tools.loadModuleAndReplace(mainConfig.save_api_accessor, 'class'));
            return promises;
        },

        prepareColumns: function(options) {
            var promises = [];
            // plugin
            // column views and components
            var columnsMeta = options.metadata.columns;
            _.each(columnsMeta, function(columnMeta) {
                if (columnMeta.inline_editing && columnMeta.inline_editing.editor) {
                    var editor = columnMeta.inline_editing.editor;
                    if (editor.component) {
                        promises.push(tools.loadModule(editor.component)
                            .then(function(realization) {
                                editor.component = realization;
                                if (_.isFunction(realization.processMetadata)) {
                                    return realization.processMetadata(columnMeta);
                                }
                                return realization;
                            }));
                    }
                    if (editor.view) {
                        promises.push(tools.loadModule(editor.view)
                            .then(function(realization) {
                                editor.view = realization;
                                if (_.isFunction(realization.processMetadata)) {
                                    return realization.processMetadata(columnMeta);
                                }
                                return realization;
                            }));
                    }
                }
                if (columnMeta.inline_editing && columnMeta.inline_editing.save_api_accessor &&
                    columnMeta.inline_editing.save_api_accessor['class']) {
                    promises.push(tools.loadModuleAndReplace(columnMeta.inline_editing.save_api_accessor,
                        'class'));
                }
            });

            return promises;
        }
    };

    return gridViewsBuilder;
});
