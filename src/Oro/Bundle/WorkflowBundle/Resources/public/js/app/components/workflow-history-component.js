/* global define */
/** @exports WorkflowViewerComponent */
define(function (require) {
    'use strict';

    var WorkflowHistoryComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        WorkflowHistoryView = require('../views/workflow-history-view'),
        WorkflowHistoryStateModel = require('../models/workflow-history-state-model'),
        WorkflowHistoryModel = require('../models/workflow-history-model');

    /**
     * Builds workflow history controls for undo/redo capability.
     *
     * @class WorkflowHistoryComponent
     * @augments BaseComponent
     */
    WorkflowHistoryComponent = BaseComponent.extend(/** @lends WorkflowHistoryComponent.prototype */{
        history: null,
        workflow: null,
        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var onWorkflowChange = _.debounce(_.bind(this.onWorkflowChange, this), 50);
            this.workflow = options.workflow;
            this.history = new WorkflowHistoryModel();
            new WorkflowHistoryView({
                model: this.history
            });
            this.listenTo(options.workflow.get('steps'), 'change add remove', onWorkflowChange);
            this.listenTo(options.workflow.get('transitions'), 'change add remove', onWorkflowChange);
        },

        onWorkflowChange: function () {
            var state = new WorkflowHistoryStateModel({
                steps: this.workflow.get('steps').toJSON(),
                transitions: this.workflow.get('transitions').toJSON()
            });
            this.history.pushState(state);
        },

        undo: function () {
            if (this.history.back() === true) {
                this.updateWorkflow(this.history.popState());
            }
        },
        redo: function () {
            if (this.history.forward() === true) {
                this.updateWorkflow(this.history.popState());
            }
        },
        updateWorkflow: function (state) {
            this.workflow.get('steps').reset(state.get('steps'));
            this.workflow.get('transitions').reset(state.get('transitions'));
        }

    });

    return WorkflowHistoryComponent;
});

