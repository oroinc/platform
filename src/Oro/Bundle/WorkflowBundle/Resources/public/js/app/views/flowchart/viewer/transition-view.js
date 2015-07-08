define(function(require) {
    'use strict';

    var _ = require('underscore');
    var FlowchartJsPlubmBaseView = require('../jsplumb/base-view');
    var FlowchartViewerTransitionView;

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
         * @type {Object}
         */
        defaultConnectionConfiguration: {
            detachable: false
        },

        /**
         * @type {Object}
         */
        connectorStyle: {
            strokeStyle: '#4F719A',
            lineWidth: 2,
            outlineColor: 'transparent',
            outlineWidth: 7
        },

        /**
         * @type {Object}
         */
        connectorHoverStyle: {

        },

        initialize: function(options) {
            this.connections = [];
            var optionKeysToCopy = ['areaView', 'stepCollection', 'stepCollectionView', 'transitionOverlayView'];
            if (optionKeysToCopy.length !== _.intersection(optionKeysToCopy, _.keys(options)).length) {
                throw new Error(optionKeysToCopy.join(', ') + ' options are required');
            }
            _.extend(this, _.pick(options, optionKeysToCopy));
            this.defaultConnectionConfiguration = _.extend({}, _.result(this, 'defaultConnectionConfiguration'));
            if (options.defaultConnectionConfiguration) {
                _.extend(this.defaultConnectionConfiguration, options.defaultConnectionConfiguration);
            }
            FlowchartViewerTransitionView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            this.updateStepTransitions();
            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function() {
            var debouncedUpdate = _.debounce(_.bind(function() {
                if (!this.disposed) {
                    this.updateStepTransitions();
                }
            }, this), 50);
            this.listenTo(this.model, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'add', debouncedUpdate);
            this.listenTo(this.stepCollection, 'change', debouncedUpdate);
            this.listenTo(this.stepCollection, 'remove', debouncedUpdate);
        },

        findElByStep: function(step) {
            return this.stepCollectionView.getItemView(step).el;
        },

        findConnectionByStartStep: function(startStep) {
            var connection;
            for (var i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                if (connection.startStep === startStep) {
                    return connection;
                }
            }
        },

        updateStepTransitions: function() {
            var connection;
            var startStep;
            var startSteps = this.model.getStartingSteps();
            var endStep = this.stepCollection.findWhere({name: this.model.get('step_to')});
            this.addStaleMark();
            for (var i = 0; i < startSteps.length; i++) {
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

        addStaleMark: function() {
            var connection;
            for (var i = 0; i < this.connections.length; i++) {
                connection = this.connections[i];
                connection.stale = true;
            }
        },

        removeStaleConnections: function() {
            var connection;
            for (var i = 0; i < this.connections.length; i++) {
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

        createConnection: function(startStep, endStep) {
            var jsplumbConnection;
            var overlayView;
            var transitionModel = this.model;
            var areaView = this.areaView;
            var endEl = this.findElByStep(endStep);
            var startEl = this.findElByStep(startStep);

            jsplumbConnection = this.areaView.jsPlumbInstance.connect(_.extend(
                {},
                this.defaultConnectionConfiguration,
                {
                    source: startEl,
                    target: endEl,
                    paintStyle: _.result(this, 'connectorStyle'),
                    hoverPaintStyle: _.result(this, 'connectorHoverStyle'),
                    overlays: [
                        ['Custom', {
                            create: _.bind(function() {
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
                }
            ));
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
            this.stopListening();
        }
    });

    return FlowchartViewerTransitionView;
});
