define(function(require) {
    'use strict';

    const FlowchartJsPlumbOverlayView = require('../jsplumb/overlay-view');

    const FlowchartViewerTransitionOverlayView = FlowchartJsPlumbOverlayView.extend({
        template: require('tpl-loader!oroworkflow/templates/flowchart/viewer/transition.html'),

        /**
         * @inheritdoc
         */
        constructor: function FlowchartViewerTransitionOverlayView(options) {
            FlowchartViewerTransitionOverlayView.__super__.constructor.call(this, options);
        },

        className: function() {
            const classNames = [FlowchartViewerTransitionOverlayView.__super__.className.call(this)];
            classNames.push('workflow-transition-overlay');
            return classNames.join(' ');
        }
    });

    return FlowchartViewerTransitionOverlayView;
});
