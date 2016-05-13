define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var console = window.console;

    var gridViewsBuilder = {
        /**
         * This column type is used by default for editing
         */
        DEFAULT_COLUMN_TYPE: 'string',

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

        processDatagridOptions: function(deferred, options) {
            if (tools.isMobile() || !options.metadata.inline_editing || !options.metadata.inline_editing.enable) {
                deferred.resolve();
                return;
            }
            var promises = this.preparePlugin(options)
                .concat(this.prepareColumns(options));

            $.when.apply($, promises).done(function() {
                if (!options.metadata.plugins) {
                    options.metadata.plugins = [];
                }
                options.metadata.plugins.push({
                    constructor: options.metadata.inline_editing.plugin,
                    options: options
                });
                deferred.resolve();
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

        /**
         * Init() function is required
         */
        init: function(deferred, options) {
            deferred.resolve();
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
            options.metadata.inline_editing.defaultEditorsLoadPromise =
                tools.loadModuleAndReplace(mainConfig, 'default_editors');
            promises.push(options.metadata.inline_editing.defaultEditorsLoadPromise);
            promises.push(tools.loadModuleAndReplace(mainConfig.cell_editor, 'component'));
            promises.push(tools.loadModuleAndReplace(mainConfig.save_api_accessor, 'class'));
            return promises;
        },

        prepareColumns: function(options) {
            var promises = [];
            var defaultOptions = this.getDefaultOptions();
            // plugin
            // column views and components
            var columnsMeta = options.metadata.columns;
            var behaviour = options.metadata.inline_editing.behaviour;
            _.each(columnsMeta, function(columnMeta) {
                switch (behaviour) {
                    case 'enable_all':
                        // this will enable inline editing where possible
                        if (columnMeta.inline_editing && columnMeta.inline_editing.enable === false) {
                            return;
                        }
                        break;
                    case 'enable_selected':
                        // disable by default, enable only on configured cells
                        if (!columnMeta.inline_editing || columnMeta.inline_editing.enable !== true) {
                            return;
                        }
                        break;
                    default:
                        throw new Error('Unknown behaviour');
                }
                if (!columnMeta.inline_editing) {
                    columnMeta.inline_editing = {};
                }
                if (!columnMeta.inline_editing.editor) {
                    columnMeta.inline_editing.editor = {};
                }
                var editor = columnMeta.inline_editing.editor;
                if (!editor.component) {
                    editor.component = defaultOptions.cell_editor.component;
                }
                if (!editor.view) {
                    options.metadata.inline_editing.defaultEditorsLoadPromise.then(function(defaultEditors) {
                        var realization = defaultEditors[(columnMeta.type || gridViewsBuilder.DEFAULT_COLUMN_TYPE)];
                        editor.view = realization;
                        if (realization === void 0) {
                            columnMeta.inline_editing.enable = false;
                            columnMeta.inline_editing.enable$changeReason =
                                'Automatically disabled due to absent editor realization';
                            if (behaviour === 'enable_selected') {
                                // if user selected this column as editable and there is no editor - show an error
                                if (console && console.error) {
                                    console.error(
                                        'Could not enable editing on grid column due to absent editor realization' +
                                        ' for type `' + columnMeta.type + '`'
                                    );
                                }
                            }
                            return;
                        }
                        if (_.isFunction(realization.processMetadata)) {
                            return realization.processMetadata(columnMeta);
                        }
                        return realization;
                    });
                } else {
                    promises.push(tools.loadModule(editor.view)
                        .then(function(realization) {
                            editor.view = realization;
                            if (_.isFunction(realization.processMetadata)) {
                                return realization.processMetadata(columnMeta);
                            }
                            return realization;
                        }));
                }

                if (_.isString(editor.component)) {
                    promises.push(tools.loadModule(editor.component)
                        .then(function(realization) {
                            editor.component = realization;
                            if (_.isFunction(realization.processMetadata)) {
                                return realization.processMetadata(columnMeta);
                            }
                            return realization;
                        }));
                } else {
                    if (_.isFunction(editor.component.processMetadata)) {
                        return editor.component.processMetadata(columnMeta);
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
