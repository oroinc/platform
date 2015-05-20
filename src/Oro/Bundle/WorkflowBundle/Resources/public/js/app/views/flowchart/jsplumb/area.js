define(function (require) {
    'use strict';
    var JsplubmBaseView = require('./base'),
        $ = require('jquery'),
        jsPlumb = require('jsplumb'),
        JsplubmAreaView;

    JsplubmAreaView = JsplubmBaseView.extend({

        jsPlumbInstance: null,

        defaultOptions: {
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
                options = $.extend(true, {}, this.defaultOptions);
                options.Container = this.cid;
                this.jsPlumbInstance = jsPlumb.getInstance(options);
            }
            return this;
        }
    });

    return JsplubmAreaView;
});
