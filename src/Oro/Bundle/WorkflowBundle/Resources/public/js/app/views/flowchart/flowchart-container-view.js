define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const errorHandler = require('oroui/js/error');
    const FlowchartControlView = require('oroworkflow/js/app/views/flowchart/viewer/flowchart-control-view');
    const ZoomableAreaView = require('oroui/js/app/views/zoomable-area-view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseView = require('oroui/js/app/views/base/view');
    const ComplexityError = require('oroworkflow/js/tools/path-finder/complexity-error');
    const errorMessageTemplate = require('tpl-loader!oroworkflow/templates/flowchart/flowchart-error-message.html');

    const FlowchartContainerView = BaseView.extend({
        autoRender: true,

        template: require('tpl-loader!oroworkflow/templates/flowchart/flowchart-container.html'),

        zoomableDefaults: {
            autozoom: true,
            minZoom: 0.05,
            maxZoom: 5
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartContainerView(options) {
            FlowchartContainerView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
                    'smartline:compute-error': this.handleFlowchartComputeError.bind(this),
                    'smartline:compute-success': this.handleFlowchartComputeSuccess.bind(this),
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
            const chartView = this.subview('chart');
            if (chartView) {
                chartView.jsPlumbManager.organizeBlocks();
            }

            const zoomView = this.subview('zoom');
            if (zoomView) {
                zoomView.model.autoZoom();
            }
        },

        handleFlowchartComputeError: function(error) {
            let data;

            if (this._isUnderComputeError) {
                return;
            }
            this._isUnderComputeError = true;

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
            if (!this._isUnderComputeError) {
                return;
            }

            this.subview('loading', new LoadingMaskView({
                container: this.$('[data-role="flowchart-container"]')
            }));
            this.subview('loading').show();

            _.delay(function() {
                this.$('[data-role="flowchart-container"]').removeClass('fixed').addClass('failed');
                this.$('[data-role="flowchart-error"]').html(errorMessageTemplate(data));
                this.subview('loading').hide();
            }.bind(this), 1000);
        },

        handleFlowchartComputeSuccess: function() {
            if (!this._isUnderComputeError) {
                return;
            }
            delete this._isUnderComputeError;

            if (this.$('[data-role="flowchart-container"]').is('.failed')) {
                this.$('[data-role="flowchart-container"]').removeClass('failed').addClass('fixed');
                this.$('[data-role="flowchart-error"]').html('');
            }
        }
    });

    return FlowchartContainerView;
});
