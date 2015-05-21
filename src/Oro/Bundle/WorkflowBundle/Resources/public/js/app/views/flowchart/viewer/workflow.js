define(function (require) {
    'use strict';
    var _ = require('underscore'),
        JsplubmAreaView = require('../jsplumb/area'),
        JsplumbWorkflowStepView = require('./step'),
        JsplubmTransitionView = require('./transition'),
        JsplubmTransitionOverlayView = require('./transition-overlay'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        WorkflowFlowchartView;

    WorkflowFlowchartView = JsplubmAreaView.extend({

        transitionOverlayView: JsplubmTransitionOverlayView,

        initialize: function () {
            WorkflowFlowchartView.__super__.initialize.apply(this, arguments);
        },

        findStepModelByElement: function (el) {
            var stepCollectionView = this.stepCollectionView;
            return this.model.get('steps').find(function (model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        render: function () {
            WorkflowFlowchartView.__super__.render.apply(this, arguments);

            this.$el.addClass('workflow-flowchart-viewer');

            this.initCollectionViews();
        },

        initCollectionViews: function () {
            var stepCollectionView,
                transitionOverlayView = this.transitionOverlayView,
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
                    return new JsplumbWorkflowStepView(options);
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
                    return new JsplubmTransitionView(options);
                },
                autoRender: true
            });
        }
    });

    return WorkflowFlowchartView;
});
