define(function(require) {
    'use strict';

    const _ = require('underscore');
    const FlowchartJsPlumbBaseView = require('../jsplumb/base-view');

    const FlowchartViewerTransitionView = FlowchartJsPlumbBaseView.extend({
        /**
         * @type {FlowchartJsPlumbAreaView}
         */
        areaView: null,

        /**
         * @type {Array}
         */
        connections: null,

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function() {
            return {
                paintStyle: {
                    strokeStyle: '#bababb',
                    lineWidth: 2,
                    outlineColor: '#ffffff',
                    outlineWidth: 2
                },
                EndpointStyle: {
                    lineWidth: 10
                }
            };
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartViewerTransitionView(options) {
            FlowchartViewerTransitionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.connections = [];

            const optionKeysToCopy = ['areaView', 'stepCollection', 'stepCollectionView', 'transitionOverlayView'];
            if (optionKeysToCopy.length !== _.intersection(optionKeysToCopy, _.keys(options)).length) {
                throw new Error(optionKeysToCopy.join(', ') + ' options are required');
            }
            _.extend(this, _.pick(options, optionKeysToCopy));

            this.defaultConnectionOptions = _.extend(
                _.result(this, 'defaultConnectionOptions'),
                options.connectionOptions || {}
            );

            FlowchartViewerTransitionView.__super__.initialize.call(this, options);
        },

        render: function() {
            this.updateStepTransitions();
            if (!this.isConnected && !this.isConnecting) {
                this.isConnecting = true;
                this.connect();
                this.isConnected = true;
                delete this.isConnecting;
            }
            return this;
        },

        connect: function() {
            const debouncedUpdate = _.debounce(() => {
                if (!this.disposed) {
                    this.updateStepTransitions();
                }
            }, 50);
            this.listenTo(this.model, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'add', debouncedUpdate);
            this.listenTo(this.stepCollection, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'remove', debouncedUpdate);
        },

        findElByStep: function(step) {
            return this.stepCollectionView.getItemView(step).el;
        },

        findConnectionByStartStep: function(startStep) {
            let connection;
            for (let i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                if (connection.startStep === startStep) {
                    return connection;
                }
            }
        },

        updateStepTransitions: function() {
            let i;
            let startStep;
            let connection;
            const name = this.model.get('name');
            const startSteps = this.stepCollection.filter(function(item) {
                return item.get('allowed_transitions').indexOf(name) !== -1;
            });
            const endStep = this.stepCollection.findWhere({name: this.model.get('step_to')});
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

            this.stepCollectionView.getItemView(endStep).updateStepMinWidth();
            for (i = 0; i < startSteps.length; i++) {
                startStep = startSteps[i];
                this.stepCollectionView.getItemView(startStep).updateStepMinWidth();
            }
        },

        addStaleMark: function() {
            let connection;
            for (let i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                connection.stale = true;
            }
        },

        removeStaleConnections: function() {
            let connection;
            for (let i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                if (connection.stale && connection.jsplumbConnection._jsPlumb) {
                    this.areaView.jsPlumbInstance.detach(connection.jsplumbConnection);
                    if (connection.jsplumbConnection.overlayView) {
                        connection.jsplumbConnection.overlayView.dispose();
                    }
                }
                // if connection is detached from jsPlumb
                if (!connection.jsplumbConnection._jsPlumb) {
                    this.connections.splice(i, 1);
                    i--;
                }
            }
        },

        createConnection: function(startStep, endStep) {
            let overlayView;
            const transitionModel = this.model;
            const areaView = this.areaView;
            const overlayIsVisible = areaView.flowchartState.get('transitionLabelsVisible');
            const endEl = this.findElByStep(endStep);
            const startEl = this.findElByStep(startStep);
            const anchors = this.areaView.jsPlumbManager.getAnchors(startEl, endEl);
            const connectionOptions = _.defaults({
                source: startEl,
                target: endEl,
                connector: ['Smartline', {cornerRadius: 3, midpoint: 0.5}],
                paintStyle: _.result(this, 'connectorStyle'),
                hoverPaintStyle: _.result(this, 'connectorHoverStyle'),
                anchors: anchors,
                overlays: [
                    ['Custom', {
                        id: 'overlay',
                        create: connection => {
                            const overlay = connection.getOverlay('overlay');
                            connection.overlayView = overlayView = new this.transitionOverlayView({
                                model: transitionModel,
                                overlay: overlay,
                                areaView: areaView,
                                stepFrom: startStep
                            });
                            overlayView.render();
                            overlay.cssClass = _.result(overlayView, 'className');
                            return overlayView.$el;
                        },
                        visible: overlayIsVisible,
                        location: 0.5
                    }]
                ]
            }, this.defaultConnectionOptions);

            const jsplumbConnection = this.areaView.jsPlumbInstance.connect(connectionOptions);
            jsplumbConnection.overlayView = overlayView;
            this.connections.push({
                startStep: startStep,
                endStep: endStep,
                jsplumbConnection: jsplumbConnection
            });
        },

        cleanup: function() {
            this.addStaleMark();
            this.removeStaleConnections();
        }
    });

    return FlowchartViewerTransitionView;
});
