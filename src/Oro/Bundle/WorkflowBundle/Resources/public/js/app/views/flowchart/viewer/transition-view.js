define(function (require) {
    'use strict';

    var _ = require('underscore'),
        FlowchartJsPlubmBaseView = require('../jsplumb/base-view'),
        FlowchartViewerTransitionView;

    FlowchartViewerTransitionView = FlowchartJsPlubmBaseView.extend({
        /**
         * @type {FlowchartJsPlubmAreaView}
         */
        areaView: null,

        /**
         * @type {Array}
         */
        connections: null,

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function () {
            return {
                detachable: false,
                paintStyle: {
                    strokeStyle: '#dcdcdc',
                    lineWidth: 2,
                    outlineColor: 'transparent',
                    outlineWidth: 7
                },
                hoverPaintStyle: {
                    strokeStyle: '#caa37b'
                },
                endpointStyle: {
                    fillStyle: '#dcdcdc'
                },
                endpointHoverStyle: {
                    fillStyle: '#caa37b'
                }
            };
        },

        initialize: function (options) {
            this.connections = [];

            var optionKeysToCopy = ['areaView', 'stepCollection', 'stepCollectionView', 'transitionOverlayView'];
            if (optionKeysToCopy.length !== _.intersection(optionKeysToCopy, _.keys(options)).length) {
                throw new Error(optionKeysToCopy.join(', ') + ' options are required');
            }
            _.extend(this, _.pick(options, optionKeysToCopy));

            this.defaultConnectionOptions = _.extend(
                _.result(this, 'defaultConnectionOptions'),
                options.connectionOptions || {}
            );

            FlowchartViewerTransitionView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            this.updateStepTransitions();
            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function () {
            var debouncedUpdate = _.debounce(_.bind(function () {
                if (!this.disposed) {
                    this.updateStepTransitions();
                }
            }, this), 50);
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
                transitionModel = this.model,
                areaView = this.areaView,
                endEl = this.findElByStep(endStep),
                startEl = this.findElByStep(startStep),
                connectionOptions = _.defaults({
                    source: startEl,
                    target: endEl,
                    overlays: [
                        ['Custom', {
                            id: 'overlay',
                            create: _.bind(function (connection) {
                                var overlayView;
                                connection.overlayView = overlayView = new this.transitionOverlayView({
                                    model: transitionModel,
                                    areaView: areaView,
                                    stepFrom: startStep
                                });
                                overlayView.render();
                                connection.getOverlay('overlay').cssClass = _.result(overlayView, 'className');
                                return overlayView.$el;
                            }, this),
                            location: 0.5
                        }]
                    ]
                }, this.defaultConnectionOptions);

            jsplumbConnection = this.areaView.jsPlumbInstance.connect(connectionOptions);
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
        }
    });

    return FlowchartViewerTransitionView;
});
