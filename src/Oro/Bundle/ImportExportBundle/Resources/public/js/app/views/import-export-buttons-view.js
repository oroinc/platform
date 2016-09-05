define(function(require) {
    'use strict';

    var ImportExportButtonsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var ImportExportManager = require('oroimportexport/js/importexport-manager');

    // TODO: refactor in scope https://magecore.atlassian.net/browse/BAP-11701
    ImportExportButtonsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                importButton: '.import-btn',
                exportButton: '.export-btn',
                templateButton: '.template-btn'
            },
            data: {}
        },

        $importButton: null,
        $exportButton: null,
        $templateButton: null,

        /**
         * @property {ImportExportManager}
         */
        importExportManager: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$importButton = this.$el.find(this.options.selectors.importButton);
            this.$exportButton = this.$el.find(this.options.selectors.exportButton);
            this.$templateButton = this.$el.find(this.options.selectors.templateButton);

            this.$importButton.on('click' + this.eventNamespace(), _.bind(this.onImportClick, this));
            this.$exportButton.on('click' + this.eventNamespace(), _.bind(this.onExportClick, this));
            this.$templateButton.on('click' + this.eventNamespace(), _.bind(this.onTemplateClick, this));

            this.importExportManager = new ImportExportManager(this.options.data);
        },

        /**
         * @param {jQuery.Event} e
         */
        onImportClick: function(e) {
            e.preventDefault();

            this.importExportManager.handleImport();
        },

        /**
         * @param {jQuery.Event} e
         */
        onExportClick: function(e) {
            e.preventDefault();

            this.importExportManager.handleExport();
        },

        /**
         * @param {jQuery.Event} e
         */
        onTemplateClick: function(e) {
            e.preventDefault();

            this.importExportManager.handleTemplate();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.importExportManager;

            this.$importButton.off('click' + this.eventNamespace());
            this.$exportButton.off('click' + this.eventNamespace());
            this.$templateButton.off('click' + this.eventNamespace());

            ImportExportButtonsView.__super__.dispose.call(this);
        }
    });

    return ImportExportButtonsView;
});
