define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ZoomStateModel = require('oroui/js/app/models/zoom-state-model');
    const ZoomControlsView = require('./zoom-controls-view');

    require('jquery.mousewheel');

    const ZoomAreaView = BaseView.extend({
        autoRender: true,

        listen: {
            'change model': 'render',
            'change:zoom model': 'notifyChangeZoom'
        },

        events: {
            mousewheel: 'onMouseWheel',
            mousedown: 'onMouseDown',
            zoomin: 'onZoomIn',
            zoomout: 'onZoomOut',
            autozoom: 'onZoomAuto'
        },

        scrollHintContainerClass: 'zoom-scroll-hint',

        scrollHintLabel: 'oro.ui.zoom.scroll_hint',

        scrollHintTemplate: require('tpl-loader!../../../templates/zoom-scroll-hint.html'),

        scrollHintDelay: 1000,

        /**
         * @inheritdoc
         */
        constructor: function ZoomAreaView(options) {
            ZoomAreaView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'scrollHintContainerClass', 'scrollHintLabel', 'scrollHintDelay'));

            ZoomAreaView.__super__.initialize.call(this, options);
            this.$zoomedElement = this.$el.find('>*:first');
            if (!this.model) {
                const initialValues = {
                    zoomLevel: 1,
                    dx: 0,
                    dy: 0
                };
                _.extend(initialValues, _.pick(options, 'minZoom', 'maxZoom'));
                this.model = new ZoomStateModel(initialValues, {
                    wrapper: this.el,
                    inner: this.$zoomedElement[0]
                });
            }
            this.$el.addClass('zoomable-area');
            if (options.autozoom) {
                this.model.autoZoom();
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.hideScrollHint();

            ZoomAreaView.__super__.dispose.call(this);
        },

        showScrollHint: function() {
            if (this.scrollHintTimeoutId) {
                clearTimeout(this.scrollHintTimeoutId);
            } else {
                this.$el.append(this.scrollHintTemplate({
                    containerClass: this.scrollHintContainerClass,
                    label: __(this.scrollHintLabel)
                }));
            }

            this.scrollHintTimeoutId = setTimeout(this.hideScrollHint.bind(this), this.scrollHintDelay);
        },

        hideScrollHint: function() {
            if (!this.scrollHintTimeoutId) {
                return;
            }

            clearTimeout(this.scrollHintTimeoutId);
            delete this.scrollHintTimeoutId;
            this.$el.find('.' + this.scrollHintContainerClass).remove();
        },

        onMouseWheel: function(event, delta, deltaX, deltaY) {
            if (event.ctrlKey || event.altKey || event.metaKey) {
                event.preventDefault();

                this.hideScrollHint();

                const clientRect = this.el.getBoundingClientRect();
                const dx = event.clientX - clientRect.left;
                const dy = event.clientY - clientRect.top;

                if (deltaY > 0) {
                    this.model.zoomIn(dx, dy);
                } else {
                    this.model.zoomOut(dx, dy);
                }
            } else {
                this.showScrollHint();
            }
        },

        onMouseDown: function(event) {
            let currentPosition = {
                x: event.originalEvent.screenX,
                y: event.originalEvent.screenY
            };
            const handleMove = event => {
                this.model.move(event.screenX - currentPosition.x, event.screenY - currentPosition.y);
                currentPosition = {
                    x: event.screenX,
                    y: event.screenY
                };
                return false;
            };
            const handleMouseUp = () => {
                $(document.body).removeClass('force-grabbed-cursor');
                removeEventListener('mousemove', handleMove, true);
                removeEventListener('mouseup', handleMouseUp, true);
                return false;
            };
            $(document.body).addClass('force-grabbed-cursor');
            addEventListener('mousemove', handleMove, true);
            addEventListener('mouseup', handleMouseUp, true);
        },

        notifyChangeZoom: function() {
            $(document).trigger('zoomchange', {
                el: this.el,
                zoom: this.model.get('zoom')
            });
        },

        onZoomIn: function() {
            this.model.zoomIn();
        },

        onZoomOut: function() {
            this.model.zoomOut();
        },

        onZoomAuto: function() {
            this.model.autoZoom();
        },

        render: function() {
            if (this.controls !== false && !this.subview('controls')) {
                const el = $('<div class="zoom-controls"></div>');
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
