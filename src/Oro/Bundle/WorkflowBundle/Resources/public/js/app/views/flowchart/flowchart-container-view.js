define(function(require) {
    'use strict';

    var FlowchartContainerView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var errorHandler = require('oroui/js/error');
    var FlowchartControlView = require('oroworkflow/js/app/views/flowchart/viewer/flowchart-control-view');
    var ZoomableAreaView = require('oroui/js/app/views/zoomable-area-view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseView = require('oroui/js/app/views/base/view');
    var ComplexityError = require('oroworkflow/js/tools/path-finder/complexity-error');
    var errorMessageTemplate = require('tpl!oroworkflow/templates/flowchart/flowchart-error-message.html');

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
            this.subview('chart', new FlowchartView(_.extend({
                autoRender: true,
                el: this.$('[data-role="flowchart"]'),
                chartHandlers: {
                    'smartline:error': _.once(this.handleFlowchartError.bind(this)),
                    'step:drag-start': function() {
                        this._isUnderDragAction = true;
                    }.bind(this),
                    'step:drag-stop': function() {
                        delete this._isUnderDragAction;
                        this.trigger('step:drag-stop');
                    }.bind(this)
                }
            }, flowchartOptions)));

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
        },

        handleFlowchartError: function(error) {
            var data;

            if (error instanceof ComplexityError) {
                data = {
                    type: 'info',
                    message: __('oro.workflow.error.complexity')
                };
            } else {
                data = {
                    type: 'error',
                    message: __('oro.workflow.error.unknown')
                };
                errorHandler.showErrorInConsole(error);
            }

            if (!this._isUnderDragAction) {
                this._showFlowchartError(data);
            } else {
                this.listenToOnce(this, 'step:drag-stop', this._showFlowchartError.bind(this, data));
            }
        },

        _showFlowchartError: function(data) {
            this.subview('loading', new LoadingMaskView({
                container: this.$('[data-role="flowchart-container"]')
            }));
            this.subview('loading').show();

            _.delay(function() {
                this.$('[data-role="flowchart-container"]')
                    .before(errorMessageTemplate(data))
                    .addClass('failed');
                this.subview('loading').hide();
                this.subviews.forEach(this.removeSubview.bind(this));
            }.bind(this), 1000);
        }
    });

    return FlowchartContainerView;
});
