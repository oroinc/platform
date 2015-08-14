define(function(require) {
    'use strict';

    var FlowchartEditorWorkflowView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var FlowchartViewerWorkflowView = require('../viewer/workflow-view');
    var FlowChartEditorTransitionOverlayView = require('./transition-overlay-view');
    var FlowchartEditorStepView = require('./step-view');

    FlowchartEditorWorkflowView = FlowchartViewerWorkflowView.extend({

        autoRender: true,
        isConnected: false,

        transitionOverlayView: FlowChartEditorTransitionOverlayView,
        stepView: FlowchartEditorStepView,
        className: 'workflow-flowchart-editor',

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function() {
            return {
                detachable: true
            };
        },

        connect: function() {
            FlowchartEditorWorkflowView.__super__.connect.apply(this, arguments);
            this.jsPlumbInstance.bind('connectionDrag', _.bind(this.onConnectionDragStart, this));
            this.jsPlumbInstance.bind('connectionDragStop', _.bind(this.onConnectionDragStop, this));
            this.jsPlumbInstance.bind('beforeDrop', _.bind(this.onBeforeConnectionDrop, this));
            this.jsPlumbInstance.bind('beforeDetach', _.bind(this.onBeforeConnectionDetach, this));
        },

        onConnectionDragStart: function(connection) {
            $('#' + connection.sourceId).addClass('connection-source');
            this.$el.addClass('workflow-drag-connection');
        },

        onConnectionDragStop: function(connection) {
            $('#' + connection.sourceId).removeClass('connection-source');
            this.$el.removeClass('workflow-drag-connection');
        },

        onBeforeConnectionDrop: function(data) {
            var transitionModel;
            var transitionName;
            var startingSteps;
            var suspendedStep;
            var stepFrom = this.findStepModelByElement(data.connection.source);
            var stepTo = this.findStepModelByElement(data.connection.target);
            if (data.connection.suspendedElement && !stepTo.get('_is_start')) {
                transitionModel = data.connection.overlayView.model;
                transitionName = transitionModel.get('name');
                startingSteps = this.model.get('steps').filter(function(item) {
                    return item.get('allowed_transitions').indexOf(transitionName) !== -1;
                });
                if (stepTo.get('name') !== transitionModel.get('step_to')) {
                    // stepTo changed
                    transitionModel.set({
                        step_to: stepTo.get('name')
                    });
                }
                if (startingSteps.indexOf(stepFrom) === -1) {
                    suspendedStep = this.findStepModelByElement(data.connection.suspendedElement);
                    stepFrom.getAllowedTransitions().add(transitionModel);
                    suspendedStep.getAllowedTransitions().remove(transitionModel);
                    stepFrom.trigger('change');
                    suspendedStep.trigger('change');
                }
            } else if (!stepTo.get('_is_start')) {
                this.model.trigger('requestAddTransition', stepFrom, stepTo);
            } else {
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    __(
                        'Cannot set end step to <i>(Start)</i> step. Please select another one'
                    )
                );
            }
            // never allow jsplumb just draw new connections, create connection model instead
            return false;
        },

        isConnectionInDrag: function(jsPlumbConnection) {
            return !jsPlumbConnection.endpoints[0].connections.length ||
                !jsPlumbConnection.endpoints[1].connections.length;
        },

        willConnectionChange: function(jsPlumbConnection) {
            return jsPlumbConnection[jsPlumbConnection.suspendedElementType] !== jsPlumbConnection.suspendedElement;
        },

        onBeforeConnectionDetach: function(jsPlumbConnection) {
            if (!this.isConnectionInDrag(jsPlumbConnection)) {
                return true;
            }
            if (this.willConnectionChange(jsPlumbConnection)) {
                return true;
            }
            return false;
        }
    });

    return FlowchartEditorWorkflowView;
});
