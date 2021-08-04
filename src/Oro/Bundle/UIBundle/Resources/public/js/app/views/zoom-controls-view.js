define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

    const ZoomControlsView = BaseView.extend({
        autoRender: true,
        template: require('tpl-loader!../../../templates/zoom-controls.html'),

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
         * @inheritdoc
         */
        constructor: function ZoomControlsView(options) {
            ZoomControlsView.__super__.constructor.call(this, options);
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
