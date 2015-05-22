define(function (require) {
    'use strict';
    var FlowchartJsPlubmBaseView = require('./base-view'),
        _ = require('underscore'),
        FlowchartJsPlubmAreaView = require('./area-view'),
        FlowchartJsPlubmBoxView;

    FlowchartJsPlubmBoxView = FlowchartJsPlubmBaseView.extend({
        areaView: null,

        className: 'jsplumb-box',

        isConnected: false,

        listen: {
            'change model': 'render'
        },

        initialize: function (options) {
            if (!(options.areaView instanceof FlowchartJsPlubmAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            FlowchartJsPlubmBoxView.__super__.initialize.apply(this, arguments);

            // append $el to the area view
            this.areaView.$el.append(this.$el);
        },

        render: function () {
            FlowchartJsPlubmBoxView.__super__.render.apply(this, arguments);

            this.checkConnected();
        },

        checkConnected: function () {
            this.ensureId();
            if (!this.isConnected) {
                this.isConnected = true;
                if (this.model.get('position')) {
                    this.$el.css({
                        top: this.model.get('position')[1],
                        left: this.model.get('position')[0]
                    });
                }
            }
        },

        cleanup: function () {
            var instance = this.areaView.jsPlumbInstance;
            instance.detach(this.$el);
        }
    });

    return FlowchartJsPlubmBoxView;
});
