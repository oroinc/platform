define(function(require) {
    'use strict';

    var FlowchartContainerView;
    var FlowchartControlView = require('oroworkflow/js/app/views/flowchart/viewer/flowchart-control-view');
    var ZoomableAreaView = require('oroui/js/app/views/zoomable-area-view');
    var BaseView = require('oroui/js/app/views/base/view');

    FlowchartContainerView = BaseView.extend({
        autoRender: true,

        template: require('tpl!oroworkflow/templates/flowchart/flowchart-container.html'),

        /**
         * @inheritDoc
         */
        constructor: function FlowchartContainerView(options) {
            FlowchartContainerView.__super__.constructor.call(this, options);
        },

        createFlowchartView: function(FlowchartView, flowchartOptions) {
            var flowchartView = new FlowchartView(_.extend({
                autoRender: true,
                el: this.$('.workflow-flowchart')
            }, flowchartOptions));
            this.subview('chart', flowchartView);

            var controlView = new FlowchartControlView({
                model: flowchartOptions.flowchartState,
                el: this.$('.workflow-flowchart-controls')
            });
            this.subview('controls', controlView);

            var zoomView = new ZoomableAreaView({
                el: this.$('.workflow-flowchart-wrapper'),
                autozoom: true,
                minZoom: 0.05,
                maxZoom: 5
            });
            this.subview('zoom', zoomView);
        }
    });

    return FlowchartContainerView;
});
