define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const widgetManager = require('oroui/js/widget-manager');
    const exportHandler = require('oroimportexport/js/export-handler');
    const messenger = require('oroui/js/messenger');

    const ExportConfigurableTemplateWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['downloadMessage', 'wid']),

        events: {
            'submit form': 'onFormSubmit'
        },

        /**
         * @inheritdoc
         */
        constructor: function ExportConfigurableTemplateWidgetView(options) {
            ExportConfigurableTemplateWidgetView.__super__.constructor.call(this, options);
        },

        onFormSubmit: function(e) {
            e.preventDefault();
            const currentTarget = e.currentTarget;
            const downloadingMessage = messenger.notificationMessage('info', this.downloadMessage);

            widgetManager.getWidgetInstance(this.wid, function(widget) {
                widget.remove();
            });

            $.ajax({
                url: currentTarget.action,
                method: currentTarget.method,
                data: $(currentTarget).serialize(),
                errorHandlerMessage: false,
                success: function(data) {
                    if (typeof data.url === 'undefined') {
                        exportHandler.handleDataTemplateDownloadErrorMessage();
                    } else {
                        window.open(data.url, '_blank');
                    }
                },
                error: function() {
                    exportHandler.handleDataTemplateDownloadErrorMessage();
                },
                complete: function() {
                    downloadingMessage.close();
                }
            });
        }
    });

    return ExportConfigurableTemplateWidgetView;
});
