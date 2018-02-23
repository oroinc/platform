define(function(require) {
    'use strict';

    var ExportButtonView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var ImportExportManager = require('oroimportexport/js/importexport-manager');

    ExportButtonView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            entity: null,
            routeOptions: {},
            exportTitle: 'Export',
            exportProcessor: null,
            exportJob: null,
            isExportPopupRequired: false,
            filePrefix: null
        },

        /**
         * @property {ImportExportManager}
         */
        importExportManager: null,

        /**
         * @inheritDoc
         */
        constructor: function ExportButtonView() {
            ExportButtonView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (!this.options.exportProcessor) {
                return;
            }

            this.$el.on('click' + this.eventNamespace(), _.bind(this.onExportClick, this));
            this.importExportManager = new ImportExportManager(this.options);
        },

        /**
         * @param {jQuery.Event} e
         */
        onExportClick: function(e) {
            e.preventDefault();

            this.importExportManager.handleExport();
        }
    });

    return ExportButtonView;
});
