define(function (require) {
    'use strict';
    var $ = require('jquery'),
        _ = require('underscore'),
        jsPlumb = require('jsplumb'),
        FlowchartJsPlubmBaseView = require('./base-view'),
        FlowchartJsPlubmAreaView;

    FlowchartJsPlubmAreaView = FlowchartJsPlubmBaseView.extend({

        jsPlumbInstance: null,

        /**
         * @type {function(): Object|Object}
         */
        defaultsChartOptions: function () {
            return {
                Endpoint: ['Dot', {
                    radius: 3,
                    cssClass: 'workflow-transition-endpoint',
                    hoverClass: 'workflow-transition-endpoint-hover'
                }],
                ConnectionOverlays: [
                    ['Arrow', {
                        location: 1,
                        id: 'arrow',
                        length: 12,
                        width: 10,
                        foldback: 0.7
                    }]
                ]
            };
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.defaultsChartOptions = _.extend(
                _.result(this, 'defaultsChartOptions'),
                options.chartOptions || {}
            );
            FlowchartJsPlubmAreaView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            // do nothing except connect()
            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function () {
            var chartOptions = _.defaults({
                container: this.id()
            }, this.defaultsChartOptions);
            this.jsPlumbInstance = jsPlumb.getInstance(chartOptions);
        }
    });

    return FlowchartJsPlubmAreaView;
});
