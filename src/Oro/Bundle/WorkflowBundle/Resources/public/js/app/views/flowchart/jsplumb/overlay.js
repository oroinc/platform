define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        OverlayView;

    OverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
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
