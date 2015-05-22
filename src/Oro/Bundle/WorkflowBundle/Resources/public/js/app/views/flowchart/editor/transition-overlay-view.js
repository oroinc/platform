define(function (require) {
    'use strict';
    var FlowchartViewerOverlayView = require('../viewer/transition-overlay-view'),
        FlowChartEditorTransitionOverlayView;

    FlowChartEditorTransitionOverlayView = FlowchartViewerOverlayView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/editor/transition.html'),

        initialize: function (options) {
            this.stepFrom = options.stepFrom;
            FlowChartEditorTransitionOverlayView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'dblclick': 'triggerEditTransition',
            'click .workflow-step-edit': 'triggerEditTransition',
            'click .workflow-step-clone': 'triggerCloneTransition',
            'click .workflow-step-delete': 'triggerDeleteTransition',
            'hidden .dropdown': 'updateAttributes',
            'shown .dropdown': 'updateAttributes'
        },

        render: function () {
            FlowChartEditorTransitionOverlayView.__super__.render.apply(this, arguments);
            this.$('.dropdown').on('show.bs.dropdown', console.log.bind(console));
            this.$('.dropdown').on('hidden', console.log.bind(console));
        },

        triggerDeleteTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveTransition', this.model);
        },

        triggerEditTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditTransition', this.model, this.stepFrom);
        },

        triggerCloneTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneTransition', this.model, this.stepFrom);
        },

        updateAttributes: function () {
            // console.log('I\'m in');
        }

    });

    return FlowChartEditorTransitionOverlayView;
});
