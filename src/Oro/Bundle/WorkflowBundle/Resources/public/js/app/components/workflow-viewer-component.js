/* global define */
/** @exports WorkflowViewerComponent */
define(function (require) {
    'use strict';

    var WorkflowViewerComponent,
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        workflowModelFactory = require('../../tools/workflow-model-factory'),
        FlowchartViewerWorkflowView = require('../views/flowchart/viewer/workflow-view'),
        FlowchartControlView = require('../views/flowchart/viewer/flowchart-control-view'),
        FlowchartStateModel = require('../models/flowchart-state-model'),
        flowchartTools = require('oroworkflow/js/tools/flowchart-tools');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowViewerComponent
     * @augments BaseComponent
     */
    WorkflowViewerComponent = BaseComponent.extend(/** @lends WorkflowViewerComponent.prototype */{

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var flowchartOptions = _.pick(options, ['connectionOptions', 'chartOptions']);
            WorkflowViewerComponent.__super__.initialize.apply(this, arguments);
            this.model = workflowModelFactory.createWorkflowModel(options);
            this.flowchartState = new FlowchartStateModel();
            this.FlowchartWorkflowView = FlowchartViewerWorkflowView;
            this.initViews(options._sourceElement, flowchartOptions);
        },

        /**
         * Initializes related views
         *
         * @param {jQuery} $el root element
         * @param {Object} flowchartOptions options for the flow chart
         *  contain connectionOptions and chartOptions properties
         */
        initViews: function ($el, flowchartOptions) {
            flowchartTools.checkPositions(this.model);
            flowchartOptions = _.extend(flowchartOptions, {
                model: this.model,
                el: $el.find('.workflow-flowchart'),
                flowchartState: this.flowchartState
            });
            this.flowchartView = new this.FlowchartWorkflowView(flowchartOptions);
            this.flowchartControlView = new FlowchartControlView({
                model: this.flowchartState,
                el: $el.find('.workflow-flowchart-controls')
            });
        }

    });

    return WorkflowViewerComponent;
});
