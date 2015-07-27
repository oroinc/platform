define(function(require) {
    'use strict';

    var FlowchartJsPlumbAreaView;
    var _ = require('underscore');
    var jsPlumb = require('jsplumb');
    var JPManager = require('../../../../tools/jsplumb-manager');
    var FlowchartJsPlumbBaseView = require('./base-view');
    require('../../../../tools/jsplumb-smartline');

    FlowchartJsPlumbAreaView = FlowchartJsPlumbBaseView.extend({

        /**
         * @type {JsPlumbManager}
         */
        jsPlumbManager: null,

        jsPlumbInstance: null,

        /**
         * @type {number}
         */
        connectionWidth: 12,

        /**
         * @type {function(): Object|Object}
         */
        defaultsChartOptions: function() {
            return {
                detachable: false,
                Endpoint: ['Dot', {
                    radius: 3,
                    cssClass: 'workflow-transition-endpoint',
                    hoverClass: 'workflow-transition-endpoint-hover'
                }],
                PaintStyle: {
                    strokeStyle: '#caa37b',
                    lineWidth: 2,
                    outlineColor: 'transparent',
                    outlineWidth: 7
                },
                HoverPaintStyle: {
                    strokeStyle: '#caa37b'
                },
                EndpointStyle: {
                    fillStyle: '#dcdcdc'
                },
                EndpointHoverStyle: {
                    fillStyle: '#caa37b'
                },
                ConnectionOverlays: [
                    ['Arrow', {
                        location: 1,
                        id: 'arrow',
                        length: 10,
                        width: 8,
                        foldback: 0.7
                    }]
                ]
            };
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.defaultsChartOptions = _.extend(
                _.result(this, 'defaultsChartOptions'),
                options.chartOptions || {}
            );
            this.flowchartState = options.flowchartState;
            this.onZoomChange = _.bind(this.onZoomChange, this);
            $(document).on('zoomchange', this.onZoomChange);

            FlowchartJsPlumbAreaView.__super__.initialize.apply(this, arguments);
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            $(document).off('zoomchange', this.onZoomChange);
            FlowchartJsPlumbAreaView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            // do nothing except connect()
            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function() {
            var chartOptions = _.defaults({
                container: this.id()
            }, this.defaultsChartOptions);
            this.jsPlumbInstance = jsPlumb.getInstance(chartOptions);
            this.jsPlumbManager = new JPManager(this.jsPlumbInstance, this.model);
            var stepWithPosition = this.model.get('steps').find(function(step) {
                var position = step.get('position');
                return _.isArray(position) && position.length === 2;
            });
            // if positions of step wasn't defined
            if (_.isUndefined(stepWithPosition)) {
                this.jsPlumbManager.organizeBlocks();
            }
        },

        onZoomChange: function (event, options) {
            this.jsPlumbInstance.setZoom(options.zoom);
        }
    });

    return FlowchartJsPlumbAreaView;
});
