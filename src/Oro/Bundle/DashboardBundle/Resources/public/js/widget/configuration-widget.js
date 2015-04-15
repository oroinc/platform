/*global define*/
define(['orotranslation/js/translator', 'routing', 'oro/dialog-widget'],
    function (__, routing, DialogWidget) {
    'use strict';

    var ConfigurationWidget = DialogWidget.extend({
        initialize: function(options) {
            if (typeof options.widget === 'undefined') {
                throw new Error('Option "widget" was not specified.');
            }
            var widgetTitle = options.widget.titleContainer.first().textContent;
            this.options.el = '#widget-configuration';
            this.options.title = widgetTitle;
            this.options.url = routing.generate('oro_dashboard_configure', {
                id: options.widget.state.id
            });

            options.dialogOptions = {
                modal: true,
                minWidth: 575,
                resizable: false
            };

            ConfigurationWidget.__super__.initialize.apply(this, arguments);
        }
    });

    /**
     * @export  orodashboard/js/widget/configuration-widget
     * @class   orodashboard.ConfigurationWidget
     */
    return ConfigurationWidget;
});
