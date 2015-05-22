define(function (require) {
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

        render: function () {
            var options;
            if (!this.jsPlumbInstance) {
                this.ensureId();
                options = $.extend(true, {}, _.result(this, 'defaults'));
                options.Container = this.cid;
                this.jsPlumbInstance = jsPlumb.getInstance(options);
            }
            return this;
        }
    });

    return FlowchartJsPlubmAreaView;
});
