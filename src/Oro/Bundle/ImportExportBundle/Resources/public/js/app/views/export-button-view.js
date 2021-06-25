define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const ImportExportManager = require('oroimportexport/js/importexport-manager');

    const ExportButtonView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function ExportButtonView(options) {
            ExportButtonView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (!this.options.exportProcessor) {
                return;
            }

            this.$el.on('click' + this.eventNamespace(), this.onExportClick.bind(this));
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
