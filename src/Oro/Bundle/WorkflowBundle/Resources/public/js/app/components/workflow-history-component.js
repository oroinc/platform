/* global define */
/** @exports WorkflowHistoryComponent */
define(function (require) {
    'use strict';

    var WorkflowHistoryComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        StatefulModel = require('oroui/js/app/models/base/stateful-model'),
        WorkflowHistoryView = require('../views/workflow-history-view'),
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
            if (options.workflow instanceof StatefulModel === false) {
                throw new Error('State object should be instance of Backbone.Model');
            }
            this.workflow = options.workflow;
            this.history = new WorkflowHistoryModel();
            this.workflowHistoryView = new WorkflowHistoryView({
                model: this.history,
                el: options._sourceElement
            });
            this.listenTo(options.workflow.get('steps'), 'change add remove', onWorkflowChange);
            this.listenTo(options.workflow.get('transitions'), 'change add remove', onWorkflowChange);
            this.listenTo(this.history, 'navigate', this.onHistoryNavigate);
        },

        onWorkflowChange: function () {
            this.history.pushState(this.workflow.getState());
        },

        onHistoryNavigate: function () {
            var state = this.history.getCurrentState();
            this.workflow.setState(state);
        }

    });

    return WorkflowHistoryComponent;
});

