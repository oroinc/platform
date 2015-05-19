define(function (require) {
    var _ = require('underscore'),
        JsplubmBaseView = require('./jsplumb/base'),
        JsplumbAreaView = require('./jsplumb/area'),
        TransitionOverlayView = require('./transition-overlay'),
        JsplumbTransitionView;

    JsplumbTransitionView = JsplubmBaseView.extend({
        areaView: null,

        connections: null,

        initialize: function (options) {
            this.connections = [];
            if (!(options.areaView instanceof JsplumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.stepCollection = options.stepCollection;
            this.stepCollectionView = options.stepCollectionView;
            JsplumbTransitionView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            if (!this.isListening) {
                this.trackChanges();
                this.updateStepTransitions();
            }
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

        updateStepTransitions: function () {
            var i, startStep,
                startSteps = this.model.getStartingSteps(),
                endStep = this.stepCollection.findWhere({name: this.model.get('step_to')});
            this.addStaleMark();
            for (i = 0; i < startSteps.length; i++) {
                startStep = startSteps[i];
                this.createOrRemoveStaleMark(startStep, endStep);
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
                    if (connection.jsplumbConnection.view) {
                        connection.jsplumbConnection.view.dispose();
                    }
                    this.connections.splice(i, 1);
                    i--;
                }
            }
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

        createOrRemoveStaleMark: function (startStep, endStep) {
            var transitionModel = this.model,
                areaView = this.areaView,
                connection = this.findConnectionByStartStep(startStep),
                endEl = this.findElByStep(endStep),
                startEl = this.findElByStep(startStep);

            if (connection) {
                delete connection.stale;
            } else {
                this.connections.push({
                    startStep: startStep,
                    jsplumbConnection: this.areaView.jsPlumbInstance.connect({
                        source: startEl,
                        target: endEl,
                        overlays: [
                            ['Custom', {
                                create: function (component) {
                                    this.view = new TransitionOverlayView({
                                        model: transitionModel,
                                        areaView: areaView,
                                        stepFrom: startStep
                                    });
                                    this.view.render();
                                    return this.view.$el;
                                },
                                location: 0.5
                            }]
                        ]
                    })
                });
            }
        },

        cleanup: function () {
            this.addStaleMark();
            this.removeStaleConnections();
        }
    });

    return JsplumbTransitionView;
});
