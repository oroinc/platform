define(function(require) {
    'use strict';

    var ZoomAreaView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var ZoomStateModel = require('oroui/js/app/models/zoom-state-model');
    var ZoomControlsView = require('./zoom-controls-view');

    require('jquery.mousewheel');

    ZoomAreaView = BaseView.extend({
        autoRender: true,

        listen: {
            'change model': 'render',
            'change:zoom model': 'notifyChangeZoom'
        },

        events: {
            'mousewheel': 'onMouseWheel',
            'mousedown': 'onMouseDown'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ZoomAreaView.__super__.initialize.apply(this, arguments);
            this.$zoomedElement = this.$el.find('>*:first');
            if (!this.model) {
                this.model = new ZoomStateModel({
                    wrapper: this.el,
                    inner: this.$zoomedElement[0],
                    zoomLevel: 1,
                    dx: 0,
                    dy: 0
                });
            }
            this.$el.addClass('zoomable-area');
        },

        onMouseWheel: function (event, delta, deltaX, deltaY) {
            var clientRect = this.el.getBoundingClientRect();
            if (deltaY > 0) {
                this.model.zoomOut(event.clientX - clientRect.left, event.clientY - clientRect.top);
                event.preventDefault();
            } else {
                this.model.zoomIn(event.clientX - clientRect.left, event.clientY - clientRect.top);
                event.preventDefault();
            }
        },

        onMouseDown: function (event) {
            var _this = this;
            var currentPosition = {
                x: event.originalEvent.screenX,
                y: event.originalEvent.screenY
            };
            function handleMove(event) {
                _this.model.move(event.screenX - currentPosition.x, event.screenY - currentPosition.y);
                currentPosition = {
                    x: event.screenX,
                    y: event.screenY
                };
                return false;
            }
            function handleMouseUp() {
                $(document.body).removeClass('force-grabbed-cursor');
                removeEventListener('mousemove', handleMove, true);
                removeEventListener('mouseup', handleMouseUp, true);
                return false;
            }
            $(document.body).addClass('force-grabbed-cursor');
            addEventListener('mousemove', handleMove, true);
            addEventListener('mouseup', handleMouseUp, true);
        },

        notifyChangeZoom: function () {
            $(document).trigger('zoomchange', {
                el: this.el,
                zoom: this.model.get('zoom')
            });
        },

        render: function () {
            if (this.controls !== false && !this.subview('controls')) {
                var el = $('<div class="zoom-controls"></div>');
                this.subview('controls', new ZoomControlsView({
                    el: el,
                    model: this.model
                }));
                this.$el.prepend(el);
            }
            this.$zoomedElement.css({
                transform: 'translate(' + this.model.get('dx') + 'px, ' + this.model.get('dy') + 'px)' +
                    ' scale(' + this.model.get('zoom') + ', ' + this.model.get('zoom') + ')'
            });
        }
    });

    return ZoomAreaView;
});
