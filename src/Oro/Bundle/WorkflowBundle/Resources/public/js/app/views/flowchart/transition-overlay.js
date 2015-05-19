define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        TransitionOverlayView;

    TransitionOverlayView = BaseView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/transition.html'),

        ensureAttributes: function () {
            // css class is updated by jsPlumb, use attribute instead
            this.$el.attr('data-role', 'transition-overlay');
        },

        render: function () {
            TransitionOverlayView.__super__.render.apply(this, arguments);
            this.ensureAttributes();
        }
    });

    return TransitionOverlayView;
});
