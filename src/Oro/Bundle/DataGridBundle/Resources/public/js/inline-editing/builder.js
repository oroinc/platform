define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const loadModules = require('oroui/js/app/services/load-modules');
    const error = require('oroui/js/error');
    const {validator} = require('jquery.validate');

    const inlineEdititngBuilder = {
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
            if (!options.metadata.inline_editing) {
                deferred.resolve();
                return;
            }

            const {mobile_enabled: mobileEnabled = false, enable = false} = options.metadata.inline_editing;

            if (
                (!mobileEnabled && tools.isMobile()) ||
                !options.metadata.inline_editing ||
                !enable
            ) {
                deferred.resolve();
                return;
            }
            const promises = this.preparePlugin(options)
                .concat(this.prepareColumns(options))
                // preload validation methods, since validation in the inline editing form is initialized synchronously
                .concat([validator.preloadMethods()]);

            $.when(...promises).done(function() {
                if (!options.metadata.plugins) {
                    options.metadata.plugins = [];
                }
                options.metadata.plugins.push({
                    constructor: options.metadata.inline_editing.plugin,
                    options: options
                });
                deferred.resolve();
            }).fail(function(e) {
                error.showErrorInConsole(e);
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
                    'http_method': 'PATCH'
                }
            };
        },

        preparePlugin: function(options) {
            const promises = [];
            const mainConfig = {};
            $.extend(true, mainConfig, this.getDefaultOptions(), options.metadata.inline_editing);
            options.metadata.inline_editing = mainConfig;
            promises.push(loadModules.fromObjectProp(mainConfig, 'plugin'));
            options.metadata.inline_editing.defaultEditorsLoadPromise =
                loadModules.fromObjectProp(mainConfig, 'default_editors');
            promises.push(options.metadata.inline_editing.defaultEditorsLoadPromise);
            promises.push(loadModules.fromObjectProp(mainConfig.cell_editor, 'component'));
            promises.push(loadModules.fromObjectProp(mainConfig.save_api_accessor, 'class'));
            return promises;
        },

        prepareColumns: function(options) {
            const promises = [];
            const defaultOptions = this.getDefaultOptions();
            // plugin
            // column views and components
            const columnsMeta = options.metadata.columns;
            const behaviour = options.metadata.inline_editing.behaviour;
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
                const editor = columnMeta.inline_editing.editor;
                if (!editor.component) {
                    editor.component = defaultOptions.cell_editor.component;
                }
                if (!editor.view) {
                    promises.push(options.metadata.inline_editing.defaultEditorsLoadPromise.then(function(editors) {
                        const editorView = editors[columnMeta.type || inlineEdititngBuilder.DEFAULT_COLUMN_TYPE];
                        editor.view = editorView;
                        if (editorView === void 0) {
                            columnMeta.inline_editing.enable = false;
                            columnMeta.inline_editing.enable$changeReason =
                                'Automatically disabled due to absent editor realization';
                            if (behaviour === 'enable_selected') {
                                error.showErrorInConsole(
                                    'Could not enable editing on grid column due to absent editor realization' +
                                    ' for type `' + columnMeta.type + '`'
                                );
                            }
                            return;
                        }
                        if (_.isFunction(editorView.processMetadata)) {
                            return editorView.processMetadata(columnMeta);
                        }
                        return editorView;
                    }));
                } else {
                    promises.push(loadModules(editor.view)
                        .then(function(editorView) {
                            editor.view = editorView;
                            if (_.isFunction(editorView.processMetadata)) {
                                return editorView.processMetadata(columnMeta);
                            }
                            return editorView;
                        }));
                }

                if (_.isString(editor.component)) {
                    promises.push(loadModules(editor.component)
                        .then(function(editorComponent) {
                            editor.component = editorComponent;
                            if (_.isFunction(editorComponent.processMetadata)) {
                                return editorComponent.processMetadata(columnMeta);
                            }
                            return editorComponent;
                        }));
                } else {
                    if (_.isFunction(editor.component.processMetadata)) {
                        return editor.component.processMetadata(columnMeta);
                    }
                }
                if (columnMeta.inline_editing && columnMeta.inline_editing.save_api_accessor &&
                    columnMeta.inline_editing.save_api_accessor['class']) {
                    promises.push(loadModules.fromObjectProp(columnMeta.inline_editing.save_api_accessor, 'class'));
                }
            });

            return promises;
        }
    };

    return inlineEdititngBuilder;
});
