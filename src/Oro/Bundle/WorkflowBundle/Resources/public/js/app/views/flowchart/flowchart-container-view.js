define(function(require) {
    'use strict';

    var FlowchartContainerView;
    var FlowchartControlView = require('oroworkflow/js/app/views/flowchart/viewer/flowchart-control-view');
    var ZoomableAreaView = require('oroui/js/app/views/zoomable-area-view');
    var BaseView = require('oroui/js/app/views/base/view');
    var ComplexityError = require('oroworkflow/js/tools/path-finder/complexity-error');
    var error = require('oroui/js/error');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');

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
                el: this.$('.workflow-flowchart')
            }, flowchartOptions));

            try {
                flowchartView.render();
            } catch (e) {
                if (e instanceof ComplexityError) {
                    this.$el.html(this.template({message: __('oro.workflow.error.complexity')}));
                } else {
                    this.$el.html('');
                    mediator.execute('showErrorMessage', __('oro.workflow.error.initialization'));
                    error.showErrorInConsole(e);
                }
                flowchartView.dispose();
                return;
            }

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
