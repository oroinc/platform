define(function (require) {
    'use strict';
    var _ = require('underscore'),
        FlowchartJsPlubmBaseView = require('../jsplumb/base-view'),
        FlowchartJsPlumbAreaView = require('../jsplumb/area-view'),
        FlowchartViewerTransitionView;

    FlowchartViewerTransitionView = FlowchartJsPlubmBaseView.extend({
        areaView: null,

        connections: null,

        connectorStyle: {
            strokeStyle: '#4F719A',
            lineWidth: 2,
            outlineColor: 'transparent',
            outlineWidth: 7
        },

        connectorHoverStyle: {

        },

        initialize: function (options) {
            this.connections = [];
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.stepCollection = options.stepCollection;
            this.stepCollectionView = options.stepCollectionView;
            this.transitionOverlayView = options.transitionOverlayView;
            FlowchartViewerTransitionView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            if (!this.isListening) {
                this.trackChanges();
            }
            this.updateStepTransitions();
        },

        trackChanges: function () {
            this.isListening = true;
            var debouncedUpdate = _.debounce(_.bind(this.updateStepTransitions, this), 50);
            this.listenTo(this.model, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'add', debouncedUpdate);
            this.listenTo(this.stepCollection, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'remove', debouncedUpdate);
        },

        findElByStep: function (step) {
            return this.stepCollectionView.getItemView(step).el;
        },

        findConnectionByStartStep: function (startStep) {
            var i, connection;
            for (i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                if (connection.startStep === startStep) {
                    return connection;
                }
            }
        },

        updateStepTransitions: function () {
            if (!this.model) {
                console.warn('model is undefined');
                return;
            }
            var i, startStep, connection,
                startSteps = this.model.getStartingSteps(),
                endStep = this.stepCollection.findWhere({name: this.model.get('step_to')});
            this.addStaleMark();
            for (i = 0; i < startSteps.length; i++) {
                startStep = startSteps[i];
                connection = this.findConnectionByStartStep(startStep);
                if (connection && connection.endStep === endStep) {
                    delete connection.stale;
                } else {
                    this.createConnection(startStep, endStep);
                }
            }
            this.removeStaleConnections();
        },

        addStaleMark: function () {
            var i, connection;
            for (i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                connection.stale = true;
            }
        },

        removeStaleConnections: function () {
            var i, connection;
            for (i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                if (connection.stale) {
                    this.areaView.jsPlumbInstance.detach(connection.jsplumbConnection);
                    if (connection.jsplumbConnection.overlayView) {
                        connection.jsplumbConnection.overlayView.dispose();
                    }
                    this.connections.splice(i, 1);
                    i--;
                }
            }
        },

        createConnection: function (startStep, endStep) {
            var jsplumbConnection,
                overlayView,
                transitionModel = this.model,
                areaView = this.areaView,
                endEl = this.findElByStep(endStep),
                startEl = this.findElByStep(startStep);

            jsplumbConnection = this.areaView.jsPlumbInstance.connect({
                source: startEl,
                target: endEl,
                paintStyle: _.result(this, 'connectorStyle'),
                hoverPaintStyle: _.result(this, 'connectorHoverStyle'),
                overlays: [
                    ['Custom', {
                        create: _.bind(function () {
                            overlayView = new this.transitionOverlayView({
                                model: transitionModel,
                                areaView: areaView,
                                stepFrom: startStep
                            });
                            overlayView.render();
                            return overlayView.$el;
                        }, this),
                        location: 0.5
                    }]
                ]
            });
            jsplumbConnection.overlayView = overlayView;
            this.connections.push({
                startStep: startStep,
                endStep: endStep,
                jsplumbConnection: jsplumbConnection
            });
        },

        cleanup: function () {
            this.addStaleMark();
            this.removeStaleConnections();
            this.stopListening();
            this.isListening = false;
        }
    });

    return FlowchartViewerTransitionView;
});
