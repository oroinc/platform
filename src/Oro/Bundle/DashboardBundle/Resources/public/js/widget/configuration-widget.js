import __ from 'orotranslation/js/translator';
import routing from 'routing';
import DialogWidget from 'oro/dialog-widget';
import _ from 'underscore';

const ConfigurationWidget = DialogWidget.extend({
    /**
     * @inheritdoc
     */
    constructor: function ConfigurationWidget(options) {
        ConfigurationWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
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
                minWidth: 380,
                resizable: false,
                width: 'auto'
            },
            options.widget.options.configurationDialogOptions || {}
        );

        ConfigurationWidget.__super__.initialize.call(this, options);
    }
});

/**
 * @export  orodashboard/js/widget/configuration-widget
 * @class   orodashboard.ConfigurationWidget
 */
export default ConfigurationWidget;
