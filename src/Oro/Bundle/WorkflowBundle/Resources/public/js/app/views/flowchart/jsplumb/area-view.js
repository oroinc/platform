define(function (require) {
    'use strict';
    var FlowchartJsPlubmAreaView,
        $ = require('jquery'),
        _ = require('underscore'),
        jsPlumb = require('jsplumb'),
        JPManager = require('../../../../tools/jsplumb-manager'),
        FlowchartJsPlubmBaseView = require('./base-view');
    require('../../../../tools/jsplumb-smartline');

    FlowchartJsPlubmAreaView = FlowchartJsPlubmBaseView.extend({

        /**
         * @type {JsPlumbManager}
         */
        jsPlumbManager: null,

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

        events: {
            'mouseup': 'recalculateConnections'
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
            var options = $.extend(true, {}, _.result(this, 'defaults'));
            options.Container = this.id();
            this.jsPlumbInstance = jsPlumb.getInstance(options);
            this.jsPlumbManager = new JPManager(this.jsPlumbInstance, this.model);
            this.jsPlumbManager.organizeBlocks();
            // wait a bit while flowchart renders
            _.delay(_.bind(this.recalculateConnections, this), 100);
        },

        recalculateConnections: function () {
            this.jsPlumbManager.recalculateConnections();
        }
    });

    return FlowchartJsPlubmAreaView;
});
