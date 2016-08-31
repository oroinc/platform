define([
    'underscore',
    'jquery',
    './model-action',
    'oroimportexport/js/importexport-manager'
], function(_, $, ModelAction, ImportExportManager) {
    'use strict';

    return ModelAction.extend({
        /** @property {Object} */
        options: {
            dataGridName: undefined,
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

            var options = $.extend(this.extractVars(this.options), {
                entity: this.entity_class,
                importProcessor: this.importProcessor,
                importJob: this.importJob,
                importValidateJob: this.importValidateJob,
                exportProcessor: this.exportProcessor,
                exportJob: this.exportJob,
                exportTemplateJob: this.exportTemplateJob
            });

            this.importExportManager = new ImportExportManager(options);
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
        extractVars: function(opts) {
            var options = $.extend({}, opts);
            _.each(options, function(value, key) {
                switch (typeof value) {
                    case 'string':
                        var variable = value.substr(1);
                        if ('$' === value[0] && this.model.has(variable)) {
                            options[key] = this.model.get(variable);
                        }
                        break
                    case 'object':
                        options[key] = this.extractVars(value);
                        break
                }
            }, this);

            return options;
        }
    });
});
