define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const $ = require('jquery');
    const jsPlumb = require('jsplumb');
    const JPManager = require('../../../../tools/jsplumb-manager');
    const FlowchartJsPlumbBaseView = require('./base-view');
    require('../../../../tools/jsplumb-smartline');

    const FlowchartJsPlumbAreaView = FlowchartJsPlumbBaseView.extend({
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
                    strokeStyle: '#bababb',
                    lineWidth: 2,
                    outlineColor: 'transparent',
                    outlineWidth: 7
                },
                HoverPaintStyle: {
                    strokeStyle: '#dba91e'
                },
                EndpointStyle: {
                    fillStyle: '#bababb'
                },
                EndpointHoverStyle: {
                    fillStyle: '#dba91e'
                },
                ConnectionOverlays: [
                    ['Arrow', {
                        location: 1,
                        id: 'arrow',
                        length: 10,
                        width: 12,
                        foldback: 1
                    }]
                ]
            };
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartJsPlumbAreaView(options) {
            FlowchartJsPlumbAreaView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.defaultsChartOptions = _.extend(
                _.result(this, 'defaultsChartOptions'),
                options.chartOptions || {}
            );
            this.flowchartState = options.flowchartState;
            _.extend(this, _.pick(options, 'chartHandlers'));

            FlowchartJsPlumbAreaView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.chartHandlers;

            FlowchartJsPlumbAreaView.__super__.dispose.call(this);
        },

        delegateEvents: function(events) {
            FlowchartJsPlumbAreaView.__super__.delegateEvents.call(this, events);
            $(document).on('zoomchange' + this.eventNamespace(), this.onZoomChange.bind(this));
            return this;
        },

        undelegateEvents: function() {
            FlowchartJsPlumbAreaView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            return this;
        },

        render: function() {
            // do nothing except connect()
            if (!this.isConnected && !this.isConnecting) {
                this.isConnecting = true;
                this.connect();
                this.isConnected = true;
                delete this.isConnecting;
            }
            return this;
        },

        connect: function() {
            const chartOptions = _.defaults({
                container: this.id()
            }, this.defaultsChartOptions);
            this.jsPlumbInstance = jsPlumb.getInstance(chartOptions);
            this.jsPlumbInstance.eventBus = Object.create(Backbone.Events);
            if (this.chartHandlers) {
                this.listenTo(this.jsPlumbInstance.eventBus, this.chartHandlers);
            }
            this.debouncedRepaintEverything = _.debounce(this.repaintEverything.bind(this), 0);
            this.jsPlumbManager = new JPManager(this.jsPlumbInstance, this.model);
            const stepWithPosition = this.model.get('steps').find(function(step) {
                const position = step.get('position');
                return _.isArray(position) && position.length === 2;
            });
            // if positions of step wasn't defined
            if (_.isUndefined(stepWithPosition)) {
                this.jsPlumbManager.organizeBlocks();
            }
        },

        repaintEverything: function() {
            if (this.isConnected) {
                this.jsPlumbInstance.repaintEverything();
            }
        },

        onZoomChange: function(event, options) {
            this.jsPlumbInstance.setZoom(options.zoom);
        }
    });

    return FlowchartJsPlumbAreaView;
});
