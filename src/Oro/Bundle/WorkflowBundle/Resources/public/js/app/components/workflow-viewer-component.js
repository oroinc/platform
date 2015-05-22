/* global define */
/** @exports WorkflowFlowchartComponent */
define(function (require) {
    'use strict';

    var WorkflowViewerComponent,
        WorkflowBaseComponent = require('./workflow-base-component'),
        FlowchartViewerWorkflowView = require('../views/flowchart/viewer/workflow-view'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowViewerComponent
     * @augments WorkflowBaseComponent
     */
    WorkflowViewerComponent = WorkflowBaseComponent.extend(
        /** @lends WorkflowViewerComponent.prototype */{
            /**
             * @constructor
             * @inheritDoc
             */
            initialize: function (options) {
                WorkflowViewerComponent.__super__.initialize.apply(this, arguments);
                flowchartTools.checkPositions(this.model);
                this.flowchartView = new FlowchartViewerWorkflowView({
                    el: options._sourceElement.find('.workflow-flowchart'),
                    model: this.model
                });

                this.flowchartView.render();
            }
        });

    return WorkflowViewerComponent;
});
