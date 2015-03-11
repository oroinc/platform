/*global define*/
define(['orotranslation/js/translator', 'routing', 'oro/dialog-widget'],
    function (__, routing, DialogWidget) {
    'use strict';

    var ConfigurationWidget = DialogWidget.extend({
        initialize: function(options) {
            if (typeof options.widget === 'undefined') {
                throw new Error('Option "widget" was not specified.');
            }

            this.options.el = '#widget-configuration';
            this.options.title = __('oro.dashboard.widget_configuration_label');
            this.options.url = routing.generate('oro_dashboard_configure', {
                id: options.widget.state.id
            });

            options.dialogOptions = {
                modal: true,
                width: 575
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
