define(function (require) {
    var JsplubmBaseView = require('./jsplumb-base'),
        $ = require('jquery'),
        jsPlumb = require('jsplumb'),
        JsplubmAreaView;

    JsplubmAreaView = JsplubmBaseView.extend({

        jsPlumbInstance: null,

        defaultOptions: {
            Endpoint: ['Dot', {radius: 2}],
            HoverPaintStyle: {strokeStyle: '#1e8151', lineWidth: 2 },
            ConnectionOverlays: [
                [ 'Arrow', {
                    location: 1,
                    id: 'arrow',
                    length: 14,
                    foldback: 0.8
                } ]
            ]
        },

        initialize: function () {
            JsplubmAreaView.__super__.initialize.apply(this, arguments);
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
        },

        cleanup: function () {

        }
    });

    return JsplubmAreaView;
});
