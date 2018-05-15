define(function(require) {
    'use strict';

    var SideMenuOverlayView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!oroui/templates/side-menu-overlay.html');

    SideMenuOverlayView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'side-menu-overlay',

        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @inheritDoc
         */
        events: {
            'click [data-role="overlay-close"]': 'close'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'page:beforeChange mediator': 'onBeforeChange'
        },

        isOpen: false,

        /**
         * @inheritDoc
         */
        constructor: function SideMenuOverlayView() {
            SideMenuOverlayView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            SideMenuOverlayView.__super__.render.call(this);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function(events) {
            SideMenuOverlayView.__super__.delegateEvents.call(this, events);
            $(document).on('keyup.side-menu-overlay-close' + this.eventNamespace(), function(e) {
                if (e.keyCode === 27) {
                    this.close();
                }
            }.bind(this));
            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            SideMenuOverlayView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            return this;
        },

        /**
         * @param $menu
         * @returns {SideMenuOverlayView}
         */
        updateContent: function($menu) {
            var $menuItem = $('<li/>', {
                'class': $menu.children().last().attr('class')
            }).append(this.$('[data-role="overlay-design-helper"]'));

            $menu.append($menuItem);

            this.$('[data-role="overlay-content"]').html($menu);

            return this;
        },

        /**
         * @param title
         */
        setTitle: function(title) {
            this.$('[data-role="overlay-title"]').text(title);

            return this;
        },

        /**
         * Action fot open
         */
        open: function() {
            this.isOpen = true;
            this.$el.addClass('open');
        },

        /**
         *  Action for close
         */
        close: function() {
            this.isOpen = false;
            this.$el.removeClass('open');
        },

        /**
         *  Action on before page change
         */
        onBeforeChange: function() {
            if (this.isOpen) {
                this.close();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.isOpen;
            $(document).off('.side-menu-overlay-close');

            SideMenuOverlayView.__super__.dispose.call(this);
        }
    });
    return SideMenuOverlayView;
});
