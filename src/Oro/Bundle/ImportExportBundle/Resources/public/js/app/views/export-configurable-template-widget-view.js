import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';
import widgetManager from 'oroui/js/widget-manager';
import exportHandler from 'oroimportexport/js/export-handler';
import messenger from 'oroui/js/messenger';

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

export default ExportConfigurableTemplateWidgetView;
