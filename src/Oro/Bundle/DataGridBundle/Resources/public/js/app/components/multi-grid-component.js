import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import widgetManager from 'oroui/js/widget-manager';
import BaseComponent from 'oroui/js/app/components/base/component';
import MultiGridView from 'orodatagrid/js/app/views/multi-grid-view';

/**
 * @exports MultiGridComponent
 */
const MultiGridComponent = BaseComponent.extend({
    /**
     * keys
     * - dialogWidgetName: name of the widget
     * - gridWidgetName: name of the grid widget
     * - items: array of objects with keys
     *      - label
     *      - gridName
     *      - className
     * - params: object with keys
     *      - grid_query: grid parameters
     */
    options: {},

    contextView: null,

    /**
     * @inheritdoc
     */
    constructor: function MultiGridComponent(options) {
        MultiGridComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = options;
        this.initView();
        this.contextView.render();
        this._bindGridEvent();
    },

    initView: function() {
        let params = this.options.params || {};
        let routeParams = {};

        widgetManager.getWidgetInstanceByAlias(this.options.dialogWidgetName, function(widget) {
            routeParams = widget.options.routeParams || {};
        });
        if (!_.isEmpty(routeParams)) {
            params = _.extend({}, params, {routeParams: routeParams});
        }

        this.contextView = new MultiGridView({
            items: this.options.items || [],
            el: this.options._sourceElement,
            params: params,
            dialogWidgetName: this.options.dialogWidgetName,
            gridWidgetName: this.options.gridWidgetName
        });
    },

    /**
     * Bind event handlers on grid widget
     * @protected
     */
    _bindGridEvent: function() {
        const self = this;
        const gridWidgetName = this.options.gridWidgetName;
        if (!gridWidgetName) {
            return;
        }

        widgetManager.getWidgetInstanceByAlias(gridWidgetName, function(widget) {
            widget.on('grid-row-select', self.onRowSelect.bind(self, widget));
        });
    },

    /**
     * Handles row selection on a grid
     *
     * @param {} gridWidget
     * @param {} data
     */
    onRowSelect: function(gridWidget, data) {
        const id = data.model.get('id');
        const dialogWidgetName = this.options.dialogWidgetName;
        const targetClass = this.contextView.currentTargetClass();

        mediator.trigger(
            dialogWidgetName + ':select',
            JSON.stringify({entityClass: targetClass, entityId: id})
        );

        widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function(dialogWidget) {
            dialogWidget.remove();
        });
    }
});

export default MultiGridComponent;
