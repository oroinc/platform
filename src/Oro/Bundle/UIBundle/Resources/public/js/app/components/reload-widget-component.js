import widgetManager from 'oroui/js/widget-manager';
import BaseComponent from 'oroui/js/app/components/base/component';

/**
 * Triggers reload of specified widgets on component initialization.
 */
const ReloadWidgetComponent = BaseComponent.extend({
    /**
     * @inheritDoc
     */
    optionNames: BaseComponent.prototype.optionNames.concat([
        'reloadWidgets'
    ]),

    /**
     * @property {Array}
     */
    reloadWidgets: [],

    /**
     * @inheritDoc
     */
    constructor: function ReloadWidgetComponent(options) {
        ReloadWidgetComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        ReloadWidgetComponent.__super__.initialize.call(this, options);

        for (const widgetAlias of this.reloadWidgets) {
            widgetManager.getWidgetInstanceByAlias(widgetAlias, widget => {
                widget.render();
            });
        }
    }
});

export default ReloadWidgetComponent;
