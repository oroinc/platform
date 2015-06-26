define(function(require) {
    'use strict';
    var $ = require('jquery'),
        _ = require('underscore'),
        jsPlumb = require('jsplumb'),
        FlowchartJsPlubmBaseView = require('./base-view'),
        FlowchartJsPlubmAreaView;

    FlowchartJsPlubmAreaView = FlowchartJsPlubmBaseView.extend({

        jsPlumbInstance: null,

        defaults: {
            Endpoint: ['Dot', {radius: 3}],
            EndpointStyle: {fillStyle: '#4F719A'},
            HoverPaintStyle: {strokeStyle: '#1e8151', lineWidth: 2},
            ConnectionOverlays: [
                ['Arrow', {
                    location: 1,
                    id: 'arrow',
                    length: 12,
                    width: 10,
                    foldback: 0.7
                }]
            ]
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
            var options = $.extend(true, {}, _.result(this, 'defaults'));
            options.Container = this.id();
            this.jsPlumbInstance = jsPlumb.getInstance(options);
        }
    });

    return FlowchartJsPlubmAreaView;
});
