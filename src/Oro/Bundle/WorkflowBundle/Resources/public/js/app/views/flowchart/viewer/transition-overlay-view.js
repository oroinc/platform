import FlowchartJsPlumbOverlayView from '../jsplumb/overlay-view';
import template from 'tpl-loader!oroworkflow/templates/flowchart/viewer/transition.html';

const FlowchartViewerTransitionOverlayView = FlowchartJsPlumbOverlayView.extend({
    template,

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

export default FlowchartViewerTransitionOverlayView;
