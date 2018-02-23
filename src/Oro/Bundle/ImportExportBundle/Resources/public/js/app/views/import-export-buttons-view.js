define(function(require) {
    'use strict';

    var ImportExportButtonsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var ImportExportManager = require('oroimportexport/js/importexport-manager');

    // TODO: refactor in scope https://magecore.atlassian.net/browse/BAP-11701
    ImportExportButtonsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                container: null,
                importButton: '.import-btn',
                importValidationButton: '.import-validation-btn',
                exportButton: '.export-btn',
                templateButton: '.template-btn'
            },
            data: {}
        },

        $container: null,

        $importButton: null,

        $importValidationButton: null,

        $exportButton: null,

        $templateButton: null,

        /**
         * @property {ImportExportManager}
         */
        importExportManager: null,

        /**
         * @inheritDoc
         */
        constructor: function ImportExportButtonsView() {
            ImportExportButtonsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = this.$el;
            if (this.options.selectors.container) {
                this.$container = $(this.options.selectors.container);
            }

            this.$importButton = this.$container.find(this.options.selectors.importButton);
            this.$importValidationButton = this.$container.find(this.options.selectors.importValidationButton);
            this.$exportButton = this.$container.find(this.options.selectors.exportButton);
            this.$templateButton = this.$container.find(this.options.selectors.templateButton);

            this.$importButton.on('click' + this.eventNamespace(), _.bind(this.onImportClick, this));
            this.$importValidationButton.on(
                'click' + this.eventNamespace(),
                _.bind(this.onImportValidationClick, this)
            );
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
        onImportValidationClick: function(e) {
            e.preventDefault();

            this.importExportManager.handleImportValidation();
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
