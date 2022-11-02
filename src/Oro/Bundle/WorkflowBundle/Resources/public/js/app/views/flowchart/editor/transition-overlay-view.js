define(function(require) {
    'use strict';

    const FlowchartViewerOverlayView = require('../viewer/transition-overlay-view');

    const FlowChartEditorTransitionOverlayView = FlowchartViewerOverlayView.extend({
        template: require('tpl-loader!oroworkflow/templates/flowchart/editor/transition.html'),

        className: function() {
            const classNames = [FlowChartEditorTransitionOverlayView.__super__.className.call(this)];
            classNames.push('dropdown');
            return classNames.join(' ');
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowChartEditorTransitionOverlayView(options) {
            FlowChartEditorTransitionOverlayView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.stepFrom = options.stepFrom;
            FlowChartEditorTransitionOverlayView.__super__.initialize.call(this, options);
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
