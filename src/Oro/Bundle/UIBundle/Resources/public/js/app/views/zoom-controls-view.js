define(function(require) {
    'use strict';

    var ZoomControlsView;
    var BaseView = require('oroui/js/app/views/base/view');

    ZoomControlsView = BaseView.extend({
        autoRender: true,
        template: require('tpl!../../../templates/zoom-controls.html'),

        events: {
            'click .btn-zoom-in': 'onZoomInClick',
            'click .btn-zoom-out': 'onZoomOutClick',
            'click .btn-auto-zoom': 'onAutoZoomClick'
        },

        listen: {
            'change model': 'render'
        },

        onZoomInClick: function () {
            this.model.zoomIn();
        },

        onZoomOutClick: function () {
            this.model.zoomOut();
        },

        onAutoZoomClick: function () {
            this.model.autoZoom();
        }
    });

    return ZoomControlsView;
});
