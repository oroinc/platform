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

        defaultConnectionConfiguration: {
            detachable: false
        },

        findStepModelByElement: function(el) {
            var stepCollectionView = this.stepCollectionView;
            return this.model.get('steps').find(function(model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        connect: function() {
            FlowchartViewerWorkflowView.__super__.connect.apply(this, arguments);
            this.$el.addClass(this.className);
            var stepCollectionView;
            var transitionOverlayView = this.transitionOverlayView;
            var defaultConnectionConfiguration = this.defaultConnectionConfiguration;
            var StepView = this.stepView;
            var TransitionView = this.transitionView;
            var that = this;
            var steps = this.model.get('steps');
            this.stepCollectionView = stepCollectionView = new BaseCollectionView({
                el: this.$el,
                collection: steps,
                animationDuration: 0,
                // pass areaView to each model
                itemView: function(options) {
                    options = _.extend({
                        areaView: that
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
                        areaView: that,
                        stepCollection: steps,
                        stepCollectionView: stepCollectionView,
                        transitionOverlayView: transitionOverlayView,
                        defaultConnectionConfiguration: _.extend({}, defaultConnectionConfiguration)
                    }, options);
                    return new TransitionView(options);
                },
                autoRender: true
            });

            this.subview('stepCollectionView', this.stepCollectionView);
            this.subview('transitionCollectionView', this.transitionCollectionView);
        }
    });

    return FlowchartViewerWorkflowView;
});
