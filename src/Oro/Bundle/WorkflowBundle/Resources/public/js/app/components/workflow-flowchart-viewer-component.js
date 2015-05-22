/* global define */
/** @exports WorkflowFlowchartComponent */
define(function (require) {
    'use strict';

    var WorkflowFlowchartViewerComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        FlowchartViewerWorkflowView = require('../views/flowchart/viewer/workflow-view'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowFlowchartViewerComponent
     * @augments BaseComponent
     */
    WorkflowFlowchartViewerComponent = BaseComponent.extend(/** @lends WorkflowFlowchartViewerComponent.prototype */{
        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.model = options.workflowModel;
            flowchartTools.checkPositions(this.model);
            this.view = new FlowchartViewerWorkflowView({
                el: options._sourceElement,
                model: this.model
            });

            this.view.render();
        }
    });

    return WorkflowFlowchartViewerComponent;
});
