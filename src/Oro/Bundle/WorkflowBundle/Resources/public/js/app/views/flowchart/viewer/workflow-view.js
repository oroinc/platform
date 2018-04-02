define(function(require) {
    'use strict';

    var FlowchartViewerWorkflowView;
    var _ = require('underscore');
    var FlowchartJsPlumbAreaView = require('../jsplumb/area-view');
    var FlowchartViewerStepView = require('./step-view');
    var FlowchartViewerTransitionView = require('./transition-view');
    var FlowchartViewerTransitionOverlayView = require('./transition-overlay-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    FlowchartViewerWorkflowView = FlowchartJsPlumbAreaView.extend({
        autoRender: true,
        /**
         * @type {Constructor.<FlowchartJsPlumbOverlayView>}
         */
        transitionOverlayView: FlowchartViewerTransitionOverlayView,

        /**
         * @type {Constructor.<FlowchartJsPlumbBoxView>}
         */
        stepView: FlowchartViewerStepView,

        /**
         * @type {Constructor.<FlowchartViewerTransitionView>}
         */
        transitionView: FlowchartViewerTransitionView,

        /**
         * @type {BaseCollectionView<FlowchartJsPlumbBoxView>}
         */
        stepCollectionView: null,

        /**
         * @type {BaseCollectionView<FlowchartViewerTransitionView>}
         */
        transitionCollectionView: null,

        className: 'workflow-flowchart-viewer',

        /**
         * @type {function(): Object|Object}
         */
        defaultConnectionOptions: function() {
            return {
                detachable: false
            };
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowchartViewerWorkflowView() {
            FlowchartViewerWorkflowView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            FlowchartViewerWorkflowView.__super__.initialize.apply(this, arguments);
            this.defaultConnectionOptions = _.extend(
                _.result(this, 'defaultConnectionOptions'),
                options.connectionOptions || {}
            );
        },

        findStepModelByElement: function(el) {
            var stepCollectionView = this.stepCollectionView;
            return this.model.get('steps').find(function(model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        connect: function() {
            FlowchartViewerWorkflowView.__super__.connect.apply(this, arguments);
            this.jsPlumbInstance.batch(_.bind(function() {
                this.$el.addClass(this.className);
                var stepCollectionView;
                var transitionOverlayView = this.transitionOverlayView;
                var connectionOptions = _.extend({}, this.defaultConnectionOptions);
                var StepView = this.stepView;
                var TransitionView = this.transitionView;
                var _this = this;
                var steps = this.model.get('steps');
                this.stepCollectionView = stepCollectionView = new BaseCollectionView({
                    el: this.$el,
                    collection: steps,
                    animationDuration: 0,
                    // pass areaView to each model
                    itemView: function(options) {
                        options = _.extend({
                            areaView: _this
                        }, options);
                        return new StepView(options);
                    },
                    autoRender: true
                });
                this.transitionCollectionView = new BaseCollectionView({
                    el: this.$el,
                    collection: this.model.get('transitions'),
                    animationDuration: 0,
                    // pass areaView to each model
                    itemView: function(options) {
                        options = _.extend({
                            areaView: _this,
                            stepCollection: steps,
                            stepCollectionView: stepCollectionView,
                            transitionOverlayView: transitionOverlayView,
                            connectionOptions: connectionOptions
                        }, options);
                        return new TransitionView(options);
                    },
                    autoRender: true
                });

                this.subview('stepCollectionView', this.stepCollectionView);
                this.subview('transitionCollectionView', this.transitionCollectionView);
            }, this));

            // tell zoomable-area to update zoom level
            this.$el.trigger('autozoom');
        }
    });

    return FlowchartViewerWorkflowView;
});
