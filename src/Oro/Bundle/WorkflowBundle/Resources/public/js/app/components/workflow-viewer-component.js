/* global define */
/** @exports WorkflowViewerComponent */
define(function (require) {
    'use strict';

    var WorkflowViewerComponent,
        _ = require('underscore'),
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
    WorkflowViewerComponent = BaseComponent.extend(/** @lends WorkflowViewerComponent.prototype */{

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var flowchartOptions = _.pick(options, ['connectionOptions', 'chartOptions']);
            WorkflowViewerComponent.__super__.initialize.apply(this, arguments);
            this.model = workflowModelFactory.createWorkflowModel(options);
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
                el: $el.find('.workflow-flowchart'),
                model: this.model
            });
            this.flowchartView = new FlowchartViewerWorkflowView(flowchartOptions);
            this.flowchartView.render();
        }
    });

    return WorkflowViewerComponent;
});
