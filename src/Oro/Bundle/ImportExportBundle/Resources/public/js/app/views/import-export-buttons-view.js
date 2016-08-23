define(function(require) {
    'use strict';

    var ImportExportButtonsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var exportHandler = require('oroimportexport/js/export-handler');

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
            importTitle: 'Import',
            exportTitle: 'Export',
            templateTitle: 'Template',
            gridname: null,
            afterRefreshPageMessage: null,
            refreshPageOnSuccess: false,
            isExportPopupRequired: false
        },

        $importButton: null,
        $exportButton: null,
        $templateButton: null,

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
        },

        /**
         * @param {jQuery.Event} e
         */
        onImportClick: function(e) {
            e.preventDefault();

            var widget = new DialogWidget({
                'url': e.currentTarget.href,
                'title': this.options.importTitle,
                'stateEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'width': 650,
                    'autoResize': true,
                    'modal': true,
                    'minHeight': 100
                }
            });
            widget.render();

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

        /**
         * @param {jQuery.Event} e
         */
        onExportClick: function(e) {
            e.preventDefault();

            if (this.options.isExportPopupRequired) {
                var widget = new DialogWidget({
                    'url': e.currentTarget.href,
                    'title': this.options.exportTitle,
                    'stateEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'width': 650,
                        'autoResize': true,
                        'modal': true,
                        'minHeight': 100
                    }
                });
                widget.render();
            } else {
                var exportStartedMessage = exportHandler.startExportNotificationMessage();
                $.getJSON(e.currentTarget.href, function(data) {
                    exportStartedMessage.close();
                    exportHandler.handleExportResponse(data);
                });
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onTemplateClick: function(e) {
            e.preventDefault();

            var widget = new DialogWidget({
                'url': e.currentTarget.href,
                'title': this.options.templateTitle,
                'stateEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'width': 650,
                    'autoResize': true,
                    'modal': true,
                    'minHeight': 100
                }
            });
            widget.render();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$importButton.off('click' + this.eventNamespace());
            this.$exportButton.off('click' + this.eventNamespace());
            this.$templateButton.off('click' + this.eventNamespace());

            ImportExportButtonsView.__super__.dispose.call(this);
        }
    });

    return ImportExportButtonsView;
});
