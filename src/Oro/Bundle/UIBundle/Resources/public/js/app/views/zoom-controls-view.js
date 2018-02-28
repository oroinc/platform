define(function(require) {
    'use strict';

    var ZoomControlsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    ZoomControlsView = BaseView.extend({
        autoRender: true,
        template: require('tpl!../../../templates/zoom-controls.html'),

        events: {
            'click .btn-zoom-in': 'onZoomInClick',
            'click .btn-zoom-out': 'onZoomOutClick',
            'click .btn-auto-zoom': 'onAutoZoomClick',
            'click .btn-set-zoom': 'onSetZoomClick'
        },

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function ZoomControlsView() {
            ZoomControlsView.__super__.constructor.apply(this, arguments);
        },

        onZoomInClick: function(e) {
            e.preventDefault();
            this.model.zoomIn();
        },

        onZoomOutClick: function(e) {
            e.preventDefault();
            this.model.zoomOut();
        },

        onAutoZoomClick: function(e) {
            e.preventDefault();
            this.model.autoZoom();
        },

        onSetZoomClick: function(e) {
            e.preventDefault();
            this.model.setZoom(parseFloat($(e.currentTarget).attr('data-size')) / 100);
        }
    });

    return ZoomControlsView;
});
