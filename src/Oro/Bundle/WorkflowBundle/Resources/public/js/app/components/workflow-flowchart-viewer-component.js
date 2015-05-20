/* global define */
/** @exports WorkflowFlowchartComponent */
define(function (require) {
    'use strict';

    var WorkflowFlowchartComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        WorkflowFlowchartView = require('../views/flowchart/viewer/workflow'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowFlowchartComponent
     * @augments BaseComponent
     */
    WorkflowFlowchartComponent = BaseComponent.extend(/** @lends WorkflowFlowchartComponent.prototype */{
        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.model = options.workflowModel;
            flowchartTools.checkPositions(this.model);
            this.view = new WorkflowFlowchartView({
                el: options._sourceElement,
                model: this.model
            });

            this.view.render();
        }
    });

    return WorkflowFlowchartComponent;
});
