import BaseComponent from 'oroui/js/app/components/base/component';
import GridTagBuilder from 'orosync/js/content/grid-builder';

const DataGridAllowTrackingComponent = BaseComponent.extend({
    optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

    /**
     * @inheritdoc
     */
    constructor: function DataGridAllowTrackingComponent(options) {
        DataGridAllowTrackingComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        GridTagBuilder.allowTracking(this.gridName);

        DataGridAllowTrackingComponent.__super__.initialize.call(this, options);
    }
});

export default DataGridAllowTrackingComponent;
