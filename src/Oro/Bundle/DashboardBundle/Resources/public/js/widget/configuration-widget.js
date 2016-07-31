define(['orotranslation/js/translator', 'routing', 'oro/dialog-widget', 'underscore'],
    function(__, routing, DialogWidget, _) {
    'use strict';

    var ConfigurationWidget = DialogWidget.extend({
        initialize: function(options) {
            if (typeof options.widget === 'undefined') {
                throw new Error('Option "widget" was not specified.');
            }

            this.options.el = '#widget-configuration';
            this.options.url = routing.generate('oro_dashboard_configure', {
                id: options.widget.state.id
            });
            options.dialogOptions = _.extend(
                {
                    title: __('oro.dashboard.widget_configuration_label') + ' - ' + options.widget.options.title,
                    modal: true,
                    minHeight: 50,
                    minWidth: 680,
                    resizable: false,
                    width: 'auto'
                },
                options.widget.options.configurationDialogOptions || {}
            );

            ConfigurationWidget.__super__.initialize.apply(this, arguments);
        }
    });

    /**
     * @export  orodashboard/js/widget/configuration-widget
     * @class   orodashboard.ConfigurationWidget
     */
    return ConfigurationWidget;
});
