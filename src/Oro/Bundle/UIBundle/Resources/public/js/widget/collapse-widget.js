define(['oroui/js/mediator', 'jquery', 'jquery-ui'], function(mediator, $) {
    'use strict';

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            open: false,
            openClass: 'expanded',
            animationSpeed: 250
        },

        _create: function() {
            this._super();
            this.$el = this.element;
        },

        _init: function() {
            this.$trigger = this.$el.find(this.options.trigger);
            this.$container = this.$el.find(this.options.container);

            if (this.options.open) {
                this.$el.addClass(this.options.openClass);
            }

            this.$el.addClass('init');

            this._initEvents();
        },

        _initEvents: function() {
            this._on(this.$trigger, {
                'click': this._toggle
            });
        },

        _toggle: function(event) {
            var self = this;
            var $trigger = $(event.currentTarget);
            var $container = this.$container;

            if ($trigger.attr('href')) {
                event.preventDefault();
            }

            if ($container.is(':animated')) {
                return false;
            }

            $container.slideToggle(this.options.animationSpeed, function() {
                var isOpen = $(this).is(':visible');
                var params = {
                    isOpen: isOpen,
                    $el: self.$el,
                    $rigger: $trigger,
                    $container: $container
                };

                self.$el.toggleClass(self.options.openClass, isOpen);
                $trigger.trigger('collapse:toggle', params);
                mediator.trigger('layout:adjustHeight');
            });
        }
    });

    return 'collapseWidget';
});
