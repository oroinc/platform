import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';
import widgetManager from 'oroui/js/widget-manager';
import exportHandler from 'oroimportexport/js/export-handler';
import mediator from 'oroui/js/mediator';

const ExportConfigurableWidgetView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['errorMessage', 'wid']),

    events: {
        'submit form': 'onFormSubmit'
    },

    /**
     * @inheritdoc
     */
    constructor: function ExportConfigurableWidgetView(options) {
        ExportConfigurableWidgetView.__super__.constructor.call(this, options);
    },

    onFormSubmit: function(e) {
        e.preventDefault();
        const currentTarget = e.currentTarget;

        mediator.execute('showLoading');

        $.ajax({
            url: currentTarget.action,
            method: currentTarget.method,
            data: $(currentTarget).serialize(),
            errorHandlerMessage: this.errorMessage,
            success: function(data) {
                exportHandler.handleExportResponse(data);
                widgetManager.getWidgetInstance(this.wid, function(widget) {
                    widget.remove();
                });
            }.bind(this),
            complete: function() {
                mediator.execute('hideLoading');
            }
        });
    }
});

export default ExportConfigurableWidgetView;
