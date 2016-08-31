define([
    'underscore',
    'jquery',
    'routing',
    './model-action',
    'oroimportexport/js/importexport-manager'
], function(_, $, routing, ModelAction, ImportExportManager) {
    'use strict';

    return ModelAction.extend({
        /** @property {Object} */
        options: {
            importTitle: null,
            exportTitle: null,
            dataGridName: null,
            filePrefix: null,
            refreshPageOnSuccess: null,
            afterRefreshPageMessage: null,
            routeOptions: {}
        },

        /** @property {String} */
        importProcessor: null,
        /** @property {String} */
        importJob: null,
        /** @property {String} */
        importValidateJob: null,

        /** @property {String} */
        exportProcessor: null,
        /** @property {String} */
        exportJob: null,
        /** @property {String} */
        exportTemplateJob: null,
        /** @property {Boolean} */
        isExportPopupRequired: null,

        /** @property {ImportExportManager} */
        importExportManager: null,

        /**
         * @inheritDoc
         */
        initialize: function() {
            ModelAction.__super__.initialize.apply(this, arguments);

            if (this.options.dataGridName === null) {
                this.options.dataGridName = this.datagrid.name;
            }

            this.options = this.expandOptions(this.options);

            var importRoute;
            var importRouteParams = {
                options: this.options.routeOptions,
                entity: this.entity_class || null,
                processorAlias: this.importProcessor,
                importJob: this.importJob || null,
                importValidateJob: this.importValidateJob || null
            };
            var exportRoute = this.isExportPopupRequired ? 'oro_importexport_export_config' :
                'oro_importexport_export_instant';
            var exportRouteParams = {
                options: this.options.routeOptions,
                entity: this.entity_class || null,
                processorAlias: this.exportProcessor,
                filePrefix: this.options.filePrefix || null,
                exportJob: this.exportJob || null,
                exportTemplateJob: this.exportTemplateJob || null
            };

            var importUrl = routing.generate(importRoute || 'oro_importexport_import_form', importRouteParams);
            var exportUrl = routing.generate(exportRoute || 'oro_importexport_export_instant', exportRouteParams);
            this.importExportManager = new ImportExportManager({
                exportUrl: exportUrl,
                importUrl: importUrl,
                importTitle: this.options.importTitle,
                exportTitle: this.options.exportTitle,
                gridname: this.options.dataGridName,
                refreshPageOnSuccess: this.options.refreshPageOnSuccess,
                afterRefreshPageMessage: this.options.afterRefreshPageMessage,
            });
        },

        /**
         * @inheritDoc
         */
        execute: function() {
            switch (this.type) {
                case 'import':
                    this.importExportManager.handleImport();
                    break
                case 'export':
                    this.importExportManager.handleExport();
                    break
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.importExportManager;

            ModelAction.__super__.dispose.call(this);
        },

        /**
         * @param {Object} opts
         * @returns {Object}
         */
        expandOptions: function(opts) {
            var options = $.extend({}, opts);
            _.each(options, function(value, key) {
                switch (typeof value) {
                    case 'string':
                        if (this.model.has(value)) {
                            options[key] = this.model.get(value);
                        }
                        break
                    case 'object':
                        options[key] = this.expandOptions(value);
                        break
                }
            }, this);

            return options;
        }
    });
});
