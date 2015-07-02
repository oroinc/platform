/* global define */
/** @exports WorkflowHistoryComponent */
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
            this.workflowHistoryView = new WorkflowHistoryView({
                model: this.history,
                el: options._sourceElement
            });
            this.listenTo(options.workflow.get('steps'), 'change add remove', onWorkflowChange);
            this.listenTo(options.workflow.get('transitions'), 'change add remove', onWorkflowChange);
            this.listenTo(this.history, 'change:index', this.updateWorkflow);
        },

        onWorkflowChange: function () {
            var state = new WorkflowHistoryStateModel({
                steps: this.workflow.get('steps').toJSON(),
                transitions: this.workflow.get('transitions').toJSON()
            });
            this.history.pushState(state);
        },

        updateWorkflow: function (state) {
            var state = this.history.getCurrentState();
            this.workflow.get('steps').reset(state.get('steps'));
            this.workflow.get('transitions').reset(state.get('transitions'));
        }

    });

    return WorkflowHistoryComponent;
});

