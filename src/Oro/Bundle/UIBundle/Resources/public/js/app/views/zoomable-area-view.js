define(function(require) {
    'use strict';

    var ZoomAreaView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ZoomStateModel = require('oroui/js/app/models/zoom-state-model');

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
            if (!this.model) {
                this.model = new ZoomStateModel({
                    el: this.el,
                    zoomLevel: 1,
                    dx: 0,
                    dy: 0
                });
            }
            this.$el.addClass('zoomable-area');
        },

        onMouseWheel: function (event) {
            var clientRect = this.el.getBoundingClientRect();
            if (event.originalEvent.deltaY > 0) {
                this.model.zoomIn(event.clientX - clientRect.left, event.clientY - clientRect.top);
                event.preventDefault();
            } else {
                this.model.zoomOut(event.clientX - clientRect.left, event.clientY - clientRect.top);
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
            if (this.controls && !this.subview("controls")) {
                console.log("create controls");
            }
            this.$el.find('>:first').css({
                transform: 'translate(' + this.model.get('dx') + 'px, ' + this.model.get('dy') + 'px)'
                    + ' scale(' + this.model.get('zoom') + ', ' + this.model.get('zoom') + ')'
            });
        }
    });

    return ZoomAreaView;
});
