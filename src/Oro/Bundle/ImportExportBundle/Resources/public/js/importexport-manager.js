/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var DialogWidget = require('oro/dialog-widget');
    var exportHandler = require('oroimportexport/js/export-handler');

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
            importRoute: 'oro_importexport_import_form',

            exportTitle: 'Export',
            exportProcessor: null,
            exportJob: null,
            exportRoute: 'oro_importexport_export_instant',
            exportConfigRoute: 'oro_importexport_export_config',
            isExportPopupRequired: false,

            exortTemplateTitle: 'Template',
            exportTemplateProcessor: null,
            exportTemplateJob: null,
            exportTemplateRoute: 'oro_importexport_export_template',
            exportTemplateConfigRoute: 'oro_importexport_export_template_config',
            isExportTemplatePopupRequired: false,

            filePrefix: null,
            datagridName: null,
            afterRefreshPageMessage: null,
            refreshPageOnSuccess: false,

            routeOptions: {}
        },

        /** @property {String} */
        importUrl: null,
        /** @property {String} */
        exportUrl: null,
        /** @property {String} */
        exportTemplateUrl: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            // TODO: refactor in scope https://magecore.atlassian.net/browse/BAP-11702
            this.options = _.defaults(options || {}, this.options);

            if (this.options.isExportPopupRequired) {
                this.options.exportRoute = this.options.exportConfigRoute;
            }

            if (this.options.isExportTemplatePopupRequired) {
                this.options.exportTemplateRoute = this.options.exportTemplateConfigRoute;
            }

            var routeOptions = {
                options: this.options.routeOptions,
                entity: this.options.entity,
                importJob: this.options.importJob,
                importValidateJob: this.options.importValidateJob,
                exportJob: this.options.exportJob,
                exportTemplateJob: this.options.exportTemplateJob
            };

            if (this.options.exportProcessor) {
                this.exportUrl = this._generateUrl(this.options.exportRoute, routeOptions, {
                    processorAlias: this.options.exportProcessor,
                    filePrefix: this.options.filePrefix
                });
            }

            this.importUrl = this._generateUrl(this.options.importRoute, routeOptions, {});

            if (this.options.exportTemplateProcessor) {
                this.exportTemplateUrl = this._generateUrl(this.options.exportTemplateRoute, routeOptions, {
                    processorAlias: this.options.exportTemplateProcessor
                });
            }
        },

        handleImport: function() {
            var widget = this._renderDialogWidget({
                url: this.importUrl,
                title: this.options.importTitle
            });

            if (!_.isEmpty(this.options.datagridName) || this.options.refreshPageOnSuccess) {
                var self = this;

                widget.on('importComplete', function(data) {
                    if (data.success) {
                        if (self.options.refreshPageOnSuccess) {
                            if (!_.isEmpty(self.options.afterRefreshPageMessage)) {
                                mediator.once('page:afterChange', function() {
                                    mediator.execute(
                                        'showFlashMessage',
                                        'warning',
                                        self.options.afterRefreshPageMessage
                                    );
                                });
                            }
                            mediator.execute('refreshPage');
                        } else if (!_.isEmpty(self.options.datagridName)) {
                            mediator.trigger('datagrid:doRefresh:' + self.options.datagridName);
                        }
                    }
                });
            }
        },

        handleExport: function() {
            if (this.options.isExportPopupRequired) {
                this._renderDialogWidget({
                    url: this.exportUrl,
                    title: this.options.exportTitle
                });
            } else {
                // move this logic to exportHandler
                var exportStartedMessage = exportHandler.startExportNotificationMessage();
                $.getJSON(this.exportUrl, function(data) {
                    exportStartedMessage.close();
                    exportHandler.handleExportResponse(data);
                });
            }
        },

        handleTemplate: function() {
            if (this.options.isExportTemplatePopupRequired) {
                this._renderDialogWidget({
                    url: this.exportTemplateUrl,
                    title: this.options.exportTemplateTitle
                });
            } else {
                window.open(this.exportTemplateUrl);
            }
        },

        /**
         * @param {String} route
         * @param {Object} defaultOptions
         * @param {Object} options
         * @returns {String}
         */
        _generateUrl: function(route, defaultOptions, options) {
            return routing.generate(route, $.extend({}, defaultOptions, options));
        },

        /**
         * @param {Object} options
         * @returns {DialogWidget}
         */
        _renderDialogWidget: function(options) {
            var opts = _.defaults({
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    width: 650,
                    autoResize: true,
                    modal: true,
                    minHeight: 100
                }
            }, options);

            var widget = new DialogWidget(opts);

            widget.render();

            return widget;
        }
    });

    return ImportExportManager;
});
