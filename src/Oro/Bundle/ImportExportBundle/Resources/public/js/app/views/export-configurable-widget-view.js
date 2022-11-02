define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const widgetManager = require('oroui/js/widget-manager');
    const exportHandler = require('oroimportexport/js/export-handler');
    const mediator = require('oroui/js/mediator');

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

    return ExportConfigurableWidgetView;
});
