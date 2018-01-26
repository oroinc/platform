define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var ImportDialogWidget = require('oroimportexport/js/widget/import-dialog-widget');
    var exportHandler = require('oroimportexport/js/export-handler');
    var __ = require('orotranslation/js/translator');

    // TODO: refactor in scope https://magecore.atlassian.net/browse/BAP-11702
    var ImportExportManager = function(options) {
        this.initialize(options);
    };

    _.extend(ImportExportManager.prototype, {
        /**
         * @property {Object}
         */
        options: {
            entity: null,

            importTitle: 'Import',
            importValidationTitle: 'Import Validation',
            importRoute: 'oro_importexport_import_form',
            importValidationRoute: 'oro_importexport_import_validation_form',
            importJob: null,
            importValidateJob: null,

            exportTitle: 'Export',
            exportProcessor: null,
            exportJob: null,
            exportRoute: 'oro_importexport_export_instant',
            exportConfigRoute: 'oro_importexport_export_config',
            isExportPopupRequired: false,

            exportTemplateTitle: 'Template',
            exportTemplateProcessor: null,
            exportTemplateJob: null,
            exportTemplateRoute: 'oro_importexport_export_template',
            exportTemplateConfigRoute: 'oro_importexport_export_template_config',
            isExportTemplatePopupRequired: false,

            filePrefix: null,
            datagridName: null,
            afterRefreshPageMessage: null,
            refreshPageOnSuccess: false,

            routeOptions: {},

            dialogOptions: {
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    width: 650,
                    autoResize: true,
                    modal: true,
                    minHeight: 100
                }
            }
        },

        /** @property {Object} */
        routeOptions: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.routeOptions = {
                options: this.options.routeOptions,
                entity: this.options.entity,
                importJob: this.options.importJob,
                importValidateJob: this.options.importValidateJob,
                exportJob: this.options.exportJob,
                exportTemplateJob: this.options.exportTemplateJob
            };
        },

        handleImport: function() {
            this._renderImportDialogWidget({
                url: routing.generate(this.options.importRoute, $.extend({}, this.routeOptions)),
                dialogOptions: {
                    title: this.options.importTitle
                },
                successMessage: __('oro.importexport.import.success.message'),
                errorMessage: __('oro.importexport.import.form_fail.message')
            });
        },

        handleImportValidation: function() {
            this._renderImportDialogWidget({
                url: routing.generate(this.options.importValidationRoute, $.extend({}, this.routeOptions)),
                dialogOptions: {
                    title: this.options.importValidationTitle
                },
                successMessage: __('oro.importexport.import_validation.success.message'),
                errorMessage: __('oro.importexport.import_validation.form_fail.message')
            });
        },

        handleExport: function() {
            if (!this.options.exportProcessor) {
                throw new TypeError('"exportProcessor" is required');
            }

            var exportUrl;

            if (this.options.isExportPopupRequired) {
                exportUrl = routing.generate(this.options.exportConfigRoute, $.extend({}, this.routeOptions, {
                    processorAlias: this.options.exportProcessor,
                    filePrefix: this.options.filePrefix
                }));

                this._renderDialogWidget({
                    url: exportUrl,
                    dialogOptions: {
                        title: this.options.exportTitle
                    }
                });
            } else {
                exportUrl = routing.generate(this.options.exportRoute, $.extend({}, this.routeOptions, {
                    processorAlias: this.options.exportProcessor,
                    filePrefix: this.options.filePrefix
                }));

                $.getJSON(
                    exportUrl,
                    function(data) {
                        exportHandler.handleExportResponse(data);
                    }
                );
            }
        },

        handleTemplate: function() {
            if (!this.options.exportTemplateProcessor) {
                throw new TypeError('"exportTemplateProcessor" is required');
            }

            var exportTemplateUrl;

            if (this.options.isExportTemplatePopupRequired) {
                exportTemplateUrl = routing.generate(
                    this.options.exportTemplateConfigRoute,
                    $.extend({}, this.routeOptions, {
                        processorAlias: this.options.exportTemplateProcessor
                    })
                );

                this._renderDialogWidget({
                    url: exportTemplateUrl,
                    dialogOptions: {
                        title: this.options.exportTemplateTitle
                    }
                });
            } else {
                exportTemplateUrl = routing.generate(
                    this.options.exportTemplateRoute,
                    $.extend({}, this.routeOptions, {
                        processorAlias: this.options.exportTemplateProcessor
                    })
                );

                window.open(exportTemplateUrl);
            }
        },

        /**
         * @param {Object} options
         * @param {Object|undefined} Dialog
         * @returns {DialogWidget}
         */
        _renderDialogWidget: function(options, Dialog) {
            var Widget = Dialog ? Dialog : DialogWidget;
            var opts = $.extend(true, {}, this.options.dialogOptions, options);

            var widget = new Widget(opts);

            widget.render();

            return widget;
        },

        /**
         * @param {Object} options
         * @returns {DialogWidget}
         */
        _renderImportDialogWidget: function(options) {
            return this._renderDialogWidget(options, ImportDialogWidget);
        }
    });

    return ImportExportManager;
});
