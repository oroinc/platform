/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
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
            importTitle: 'Import',
            exportTitle: 'Export',
            templateTitle: 'Template',
            gridname: null,
            afterRefreshPageMessage: null,
            refreshPageOnSuccess: false,
            isExportPopupRequired: false,
            importUrl: null,
            exportUrl: null,
            templateUrl: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
        },

        handleImport: function() {
            var widget = this._renderDialogWidget({
                url: this.options.importUrl,
                title: this.options.importTitle,
            });

            if (!_.isEmpty(this.options.gridname) || this.options.refreshPageOnSuccess) {
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
                        } else if (!_.isEmpty(self.options.gridname)) {
                            mediator.trigger('datagrid:doRefresh:' + self.options.gridname);
                        }
                    }
                });
            }
        },

        handleExport: function() {
            if (this.options.isExportPopupRequired) {
                this._renderDialogWidget({
                    url: this.options.exportUrl,
                    title: this.options.exportTitle,
                });
            } else {
                var exportStartedMessage = exportHandler.startExportNotificationMessage();
                $.getJSON(this.options.exportUrl, function(data) {
                    exportStartedMessage.close();
                    exportHandler.handleExportResponse(data);
                });
            }
        },

        handleTemplate: function() {
            if (this.options.isExportPopupRequired) {
                this._renderDialogWidget({
                    url: this.options.templateUrl,
                    title: this.options.templateTitle
                });
            } else {
                window.open(this.options.templateUrl);
            }
        },

        /**
         * @param {Object} options
         * @returns {DialogWidget}
         */
        _renderDialogWidget: function (options) {
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
