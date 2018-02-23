define(function(require) {
    'use strict';

    var FlowChartEditorTransitionOverlayView;
    var FlowchartViewerOverlayView = require('../viewer/transition-overlay-view');

    FlowChartEditorTransitionOverlayView = FlowchartViewerOverlayView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/editor/transition.html'),

        className: function() {
            var classNames = [FlowChartEditorTransitionOverlayView.__super__.className.call(this)];
            classNames.push('dropdown');
            return classNames.join(' ');
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowChartEditorTransitionOverlayView() {
            FlowChartEditorTransitionOverlayView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.stepFrom = options.stepFrom;
            FlowChartEditorTransitionOverlayView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'dblclick': 'triggerEditTransition',
            'click .workflow-step-edit': 'triggerEditTransition',
            'click .workflow-step-clone': 'triggerCloneTransition',
            'click .workflow-step-delete': 'triggerDeleteTransition'
        },

        triggerDeleteTransition: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveTransition', this.model);
        },

        triggerEditTransition: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditTransition', this.model, this.stepFrom);
        },

        triggerCloneTransition: function(e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneTransition', this.model, this.stepFrom);
        }

    });

    return FlowChartEditorTransitionOverlayView;
});
