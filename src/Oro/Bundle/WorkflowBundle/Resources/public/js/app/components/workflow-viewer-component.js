/* global define */
/** @exports WorkflowViewerComponent */
define(function(require) {
    'use strict';

    var WorkflowViewerComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        workflowModelFactory = require('../../tools/workflow-model-factory'),
        FlowchartViewerWorkflowView = require('../views/flowchart/viewer/workflow-view'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowViewerComponent
     * @augments BaseComponent
     */
    WorkflowViewerComponent = BaseComponent.extend(
        /** @lends WorkflowViewerComponent.prototype */{

            initialize: function(options) {
                WorkflowViewerComponent.__super__.initialize.apply(this, arguments);
                this._sourceElement = options._sourceElement;
                this.model = workflowModelFactory.createWorkflowModel(options);
                this.initViews();
            },

            initViews: function() {
                flowchartTools.checkPositions(this.model);
                this.flowchartView = new FlowchartViewerWorkflowView({
                    el: this._sourceElement.find('.workflow-flowchart'),
                    model: this.model
                });

                this.flowchartView.render();
            }
        });

    return WorkflowViewerComponent;
});
