/* global define */
/** @exports WorkflowFlowchartComponent */
define(function (require) {
    'use strict';

    var WorkflowFlowchartEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        FlowchartEditorWorkflowView = require('../views/flowchart/editor/workflow-view'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowFlowchartEditorComponent
     * @augments BaseComponent
     */
    WorkflowFlowchartEditorComponent = BaseComponent.extend(/** @lends WorkflowFlowchartEditorComponent.prototype */{
        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.model = options.workflowModel;
            flowchartTools.checkPositions(this.model);
            this.view = new FlowchartEditorWorkflowView({
                el: options._sourceElement,
                model: this.model
            });

            this.view.render();
        }
    });

    return WorkflowFlowchartEditorComponent;
});
