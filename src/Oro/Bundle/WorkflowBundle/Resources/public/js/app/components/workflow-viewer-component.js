/** @exports WorkflowViewerComponent */
define(function(require) {
    'use strict';

    var WorkflowViewerComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var workflowModelFactory = require('oroworkflow/js/tools/workflow-model-factory');
    var FlowchartViewerWorkflowView = require('oroworkflow/js/app/views/flowchart/viewer/workflow-view');
    var FlowchartStateModel = require('oroworkflow/js/app/models/flowchart-state-model');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowViewerComponent
     * @augments BaseComponent
     */
    WorkflowViewerComponent = BaseComponent.extend(/** @lends WorkflowViewerComponent.prototype */{
        FlowchartWorkflowView: FlowchartViewerWorkflowView,

        /**
         * @type {Array.<Promise>}
         */
        _initPromises: null,

        /**
         * @inheritDoc
         */
        constructor: function WorkflowViewerComponent() {
            this._initPromises = [];
            WorkflowViewerComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WorkflowViewerComponent.__super__.initialize.apply(this, arguments);
            this.model = workflowModelFactory.createWorkflowModel(options);

            var subComponentPromise = options._subPromises['flowchart-container'];

            if (subComponentPromise) {
                this._initPromises.push(subComponentPromise);
                this.flowchartState = new FlowchartStateModel();

                var flowchartOptions = _.extend({
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
                $.when.apply($, this._initPromises).then(function() {
                    delete this._initPromises;
                    this._resolveDeferredInit();
                }.bind(this));
            }
        },

        /**
         * @inheritDoc
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
