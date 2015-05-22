define(function (require) {
    'use strict';
    var FlowchartViewerWorkflowView,
        _ = require('underscore'),
        FlowchartJsPlumbAreaView = require('../jsplumb/area-view'),
        FlowchartViewerStepView = require('./step-view'),
        FlowchartViewerTransitionView = require('./transition-view'),
        FlowchartViewerTransitionOverlayView = require('./transition-overlay-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    FlowchartViewerWorkflowView = FlowchartJsPlumbAreaView.extend({
        transitionOverlayView: FlowchartViewerTransitionOverlayView,
        stepView: FlowchartViewerStepView,
        transitionView: FlowchartViewerTransitionView,
        stepCollectionView: null,
        transitionCollectionView: null,

        initialize: function () {
            FlowchartViewerWorkflowView.__super__.initialize.apply(this, arguments);
        },

        findStepModelByElement: function (el) {
            var stepCollectionView = this.stepCollectionView;
            return this.model.get('steps').find(function (model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        render: function () {
            FlowchartViewerWorkflowView.__super__.render.apply(this, arguments);

            this.$el.addClass('workflow-flowchart-viewer');

            this.initCollectionViews();
        },

        initCollectionViews: function () {
            var stepCollectionView,
                transitionOverlayView = this.transitionOverlayView,
                StepView = this.stepView,
                TransitionView = this.transitionView,
                that = this,
                steps = this.model.get('steps');
            this.stepCollectionView = stepCollectionView = new BaseCollectionView({
                el: this.$el,
                collection: steps,
                animationDuration: 0,
                // pass areaView to each model
                itemView: function (options) {
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
                itemView: function (options) {
                    options = _.extend({
                        areaView: that,
                        stepCollection: steps,
                        stepCollectionView: stepCollectionView,
                        transitionOverlayView: transitionOverlayView
                    }, options);
                    return new TransitionView(options);
                },
                autoRender: true
            });
        }
    });

    return FlowchartViewerWorkflowView;
});
