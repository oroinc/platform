define(function(require) {
    'use strict';

    var FlowchartJsPlubmOverlayView;
    var BaseView = require('oroui/js/app/views/base/view');
    var FlowchartJsPlubmAreaView = require('./area-view');

    FlowchartJsPlubmOverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        initialize: function(options) {
            if (!(options.areaView instanceof FlowchartJsPlubmAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            FlowchartJsPlubmOverlayView.__super__.initialize.apply(this, arguments);
        }
    });

    return FlowchartJsPlubmOverlayView;
});
