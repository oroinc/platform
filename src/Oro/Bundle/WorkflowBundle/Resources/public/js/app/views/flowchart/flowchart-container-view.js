import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import errorHandler from 'oroui/js/error';
import FlowchartControlView from 'oroworkflow/js/app/views/flowchart/viewer/flowchart-control-view';
import ZoomableAreaView from 'oroui/js/app/views/zoomable-area-view';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import BaseView from 'oroui/js/app/views/base/view';
import ComplexityError from 'oroworkflow/js/tools/path-finder/complexity-error';
import errorMessageTemplate from 'tpl-loader!oroworkflow/templates/flowchart/flowchart-error-message.html';
import template from 'tpl-loader!oroworkflow/templates/flowchart/flowchart-container.html';

const FlowchartContainerView = BaseView.extend({
    autoRender: true,

    template,

    zoomableDefaults: {
        autozoom: true,
        minZoom: 0.05,
        maxZoom: 5
    },

    /**
     * @inheritdoc
     */
    constructor: function FlowchartContainerView(options) {
        this.autoZoom = _.debounce(this.autoZoom.bind(this));
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

        this.autoZoom();
    },

    autoZoom() {
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

export default FlowchartContainerView;
