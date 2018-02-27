define(function(require) {
    'use strict';

    var ExportConfigurableWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var exportHandler = require('oroimportexport/js/export-handler');
    var mediator = require('oroui/js/mediator');

    ExportConfigurableWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['errorMessage', 'wid']),

        events: {
            'submit form': 'onFormSubmit'
        },

        /**
         * @inheritDoc
         */
        constructor: function ExportConfigurableWidgetView() {
            ExportConfigurableWidgetView.__super__.constructor.apply(this, arguments);
        },

        onFormSubmit: function(e) {
            e.preventDefault();
            var currentTarget = e.currentTarget;

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
