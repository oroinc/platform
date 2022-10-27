define(function(require) {
    'use strict';

    const _ = require('underscore');
    const FlowchartJsPlumbAreaView = require('../jsplumb/area-view');
    const FlowchartViewerStepView = require('./step-view');
    const FlowchartViewerTransitionView = require('./transition-view');
    const FlowchartViewerTransitionOverlayView = require('./transition-overlay-view');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    const FlowchartViewerWorkflowView = FlowchartJsPlumbAreaView.extend({
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
         * @inheritdoc
         */
        constructor: function FlowchartViewerWorkflowView(options) {
            FlowchartViewerWorkflowView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            FlowchartViewerWorkflowView.__super__.initialize.call(this, options);
            this.defaultConnectionOptions = _.extend(
                _.result(this, 'defaultConnectionOptions'),
                options.connectionOptions || {}
            );
        },

        findStepModelByElement: function(el) {
            const stepCollectionView = this.subview('stepCollectionView');
            return this.model.get('steps').find(function(model) {
                return stepCollectionView.getItemView(model).el === el;
            });
        },

        connect: function() {
            FlowchartViewerWorkflowView.__super__.connect.call(this);
            this.jsPlumbInstance.batch(() => {
                this.$el.addClass(this.className);
                const transitionOverlayView = this.transitionOverlayView;
                const connectionOptions = _.extend({}, this.defaultConnectionOptions);
                const StepView = this.stepView;
                const TransitionView = this.transitionView;
                const areaView = this;
                const steps = this.model.get('steps');
                const stepCollectionView = new BaseCollectionView({
                    el: this.$el,
                    collection: steps,
                    animationDuration: 0,
                    // pass areaView to each model
                    itemView: function(options) {
                        options = _.extend({
                            areaView
                        }, options);
                        return new StepView(options);
                    },
                    autoRender: true
                });
                const transitionCollectionView = new BaseCollectionView({
                    el: this.$el,
                    collection: this.model.get('transitions'),
                    animationDuration: 0,
                    // pass areaView to each model
                    itemView: function(options) {
                        options = _.extend({
                            areaView,
                            stepCollection: steps,
                            stepCollectionView: stepCollectionView,
                            transitionOverlayView: transitionOverlayView,
                            connectionOptions: connectionOptions
                        }, options);
                        return new TransitionView(options);
                    },
                    autoRender: true
                });

                this.subview('stepCollectionView', stepCollectionView);
                this.subview('transitionCollectionView', transitionCollectionView);
            });

            // tell zoomable-area to update zoom level
            this.$el.trigger('autozoom');
        }
    });

    return FlowchartViewerWorkflowView;
});
