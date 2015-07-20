define(function(require) {
    'use strict';

    var FlowchartViewerTransitionView;
    var _ = require('underscore');
    var FlowchartJsPlumbBaseView = require('../jsplumb/base-view');

    FlowchartViewerTransitionView = FlowchartJsPlumbBaseView.extend({
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
                    strokeStyle: '#dcdcdc',
                    lineWidth: 2,
                    outlineColor: 'transparent',
                    outlineWidth: 7
                }
            };
        },

        initialize: function(options) {
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
            var startStep;
            var connection;
            var name = this.model.get('name');
            var startSteps = this.stepCollection.filter(function(item) {
                    return item.get('allowed_transitions').indexOf(name) !== -1;
                });
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
            var overlayIsVisible = areaView.flowchartState.get('transitionLabelsVisible');
            var endEl = this.findElByStep(endStep);
            var startEl = this.findElByStep(startStep);
            var anchors = this.areaView.jsPlumbManager.getAnchors(startEl, endEl);
            var connectionOptions = _.defaults({
                source: startEl,
                target: endEl,
                connector: ['Smartline', {cornerRadius: 3, midpoint: 0.5}],
                paintStyle: _.result(this, 'connectorStyle'),
                hoverPaintStyle: _.result(this, 'connectorHoverStyle'),
                anchors: anchors,
                overlays: [
                    ['Custom', {
                        id: 'overlay',
                        create: _.bind(function(connection) {
                            var overlay = connection.getOverlay('overlay');
                            connection.overlayView = overlayView = new this.transitionOverlayView({
                                model: transitionModel,
                                overlay: overlay,
                                areaView: areaView,
                                stepFrom: startStep
                            });
                            overlayView.render();
                            overlay.cssClass = _.result(overlayView, 'className');
                            return overlayView.$el;
                        }, this),
                        visible: overlayIsVisible,
                        location: 0.5
                    }]
                ]
            }, this.defaultConnectionOptions);

            jsplumbConnection = this.areaView.jsPlumbInstance.connect(connectionOptions);
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
