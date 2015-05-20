define(function (require) {
    'use strict';
    var OverlayView,
        BaseView = require('oroui/js/app/views/base/view'),
        JsplumbAreaView = require('./area');

    OverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        initialize: function (options) {
            if (!(options.areaView instanceof JsplumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            OverlayView.__super__.initialize.apply(this, arguments);
        },

        ensureAttributes: function () {
            // css class is updated by jsPlumb, use attribute instead
            this.$el.attr('data-role', 'transition-overlay');
        },

        render: function () {
            OverlayView.__super__.render.apply(this, arguments);
            this.ensureAttributes();
        }
    });

    return OverlayView;
});
