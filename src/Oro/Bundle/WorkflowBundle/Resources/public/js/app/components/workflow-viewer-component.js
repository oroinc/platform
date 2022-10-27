/** @exports WorkflowViewerComponent */
define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const workflowModelFactory = require('oroworkflow/js/tools/workflow-model-factory');
    const FlowchartViewerWorkflowView = require('oroworkflow/js/app/views/flowchart/viewer/workflow-view');
    const FlowchartStateModel = require('oroworkflow/js/app/models/flowchart-state-model');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowViewerComponent
     * @augments BaseComponent
     */
    const WorkflowViewerComponent = BaseComponent.extend(/** @lends WorkflowViewerComponent.prototype */{
        FlowchartWorkflowView: FlowchartViewerWorkflowView,

        /**
         * @type {Array.<Promise>}
         */
        _initPromises: null,

        /**
         * @inheritdoc
         */
        constructor: function WorkflowViewerComponent(options) {
            this._initPromises = [];
            WorkflowViewerComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WorkflowViewerComponent.__super__.initialize.call(this, options);
            this.model = workflowModelFactory.createWorkflowModel(options);

            const subComponentPromise = options._subPromises['flowchart-container'];

            if (subComponentPromise) {
                this._initPromises.push(subComponentPromise);
                this.flowchartState = new FlowchartStateModel();

                const flowchartOptions = _.extend({
                    model: this.model,
                    flowchartState: this.flowchartState,
                    chartOptions: {},
                    connectionOptions: {}
                }, _.pick(options, 'connectionOptions', 'chartOptions'));

                subComponentPromise.then(function(flowchartContainerComponent) {
                    this.flowchartContainerView = flowchartContainerComponent.view;
                    this.flowchartContainerView.createFlowchartView(this.FlowchartWorkflowView, flowchartOptions);
                }.bind(this));
            }

            if (this._initPromises.length) {
                this._deferredInit();
                $.when(...this._initPromises).then(function() {
                    delete this._initPromises;
                    this._resolveDeferredInit();
                }.bind(this));
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.flowchartContainerView;

            WorkflowViewerComponent.__super__.dispose.call(this);
        }
    });

    return WorkflowViewerComponent;
});
