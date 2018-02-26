define(function(require) {
    'use strict';

    var ExportConfigurableTemplateWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var exportHandler = require('oroimportexport/js/export-handler');
    var messenger = require('oroui/js/messenger');

    ExportConfigurableTemplateWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['downloadMessage', 'wid']),

        events: {
            'submit form': 'onFormSubmit'
        },

        /**
         * @inheritDoc
         */
        constructor: function ExportConfigurableTemplateWidgetView() {
            ExportConfigurableTemplateWidgetView.__super__.constructor.apply(this, arguments);
        },

        onFormSubmit: function(e) {
            e.preventDefault();
            var currentTarget = e.currentTarget;
            var downloadingMessage = messenger.notificationMessage('info', this.downloadMessage);

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
