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
        defaultConnectionOptions: function () {
            return {
                detachable: false
            };
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            FlowchartViewerWorkflowView.__super__.initialize.apply(this, arguments);
            this.defaultConnectionOptions = _.extend(
                _.result(this, 'defaultConnectionOptions'),
                options.connectionOptions || {}
            );
        },

        findStepModelByElement: function (el) {
            var stepCollectionView = this.stepCollectionView;
            return this.model.get('steps').find(function (model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        connect: function () {
            FlowchartViewerWorkflowView.__super__.connect.apply(this, arguments);
            this.$el.addClass(this.className);
            var stepCollectionView,
                transitionOverlayView = this.transitionOverlayView,
                connectionOptions = _.extend({}, this.defaultConnectionOptions),
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
                        transitionOverlayView: transitionOverlayView,
                        connectionOptions: connectionOptions
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
