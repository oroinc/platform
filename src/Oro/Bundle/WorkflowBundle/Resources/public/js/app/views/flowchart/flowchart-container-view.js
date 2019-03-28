define(function(require) {
    'use strict';

    var FlowchartContainerView;
    var _ = require('underscore');
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

        zoomableDefaults: {
            autozoom: true,
            minZoom: 0.05,
            maxZoom: 5
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowchartContainerView(options) {
            FlowchartContainerView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.zoomableOptions = _.defaults(_.result(options, 'zoomableOptions', {}), this.zoomableDefaults);
            FlowchartContainerView.__super__.initialize.call(this, options);
        },

        createFlowchartView: function(FlowchartView, flowchartOptions) {
            var flowchartView = new FlowchartView(_.extend({
                el: this.$('[data-role="flowchart"]')
            }, flowchartOptions));
            this.subview('chart', flowchartView);

            try {
                flowchartView.render();
            } catch (e) {
                this.subviews.forEach(this.removeSubview.bind(this));
                if (e instanceof ComplexityError) {
                    this.$el.html(this.template({message: __('oro.workflow.error.complexity')}));
                } else {
                    this.$el.html('');
                    mediator.execute('showErrorMessage', __('oro.workflow.error.initialization'));
                    error.showErrorInConsole(e);
                }
                return;
            }

            this.subview('controls', new FlowchartControlView({
                model: flowchartOptions.flowchartState,
                el: this.$('[data-role="flowchart-controls"]')
            }));

            this.subview('zoom', new ZoomableAreaView(_.extend({
                el: this.$('[data-role="flowchart-wrapper"]')
            }, this.zoomableOptions)));
        },

        refresh: function() {
            var chartView = this.subview('chart');
            if (chartView) {
                chartView.jsPlumbManager.organizeBlocks();
            }

            var zoomView = this.subview('zoom');
            if (zoomView) {
                zoomView.model.autoZoom();
            }
        }
    });

    return FlowchartContainerView;
});
