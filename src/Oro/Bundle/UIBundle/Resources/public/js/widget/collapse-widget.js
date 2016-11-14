define(['jquery', 'oroui/js/mediator', 'underscore', 'jquery-ui'], function($, mediator, _) {
    'use strict';

    var localStorage = window.localStorage;

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            hideSibling: false,
            breakpoint: 0,
            storageKey: '',
            open: null,
            forcedState: null,
            uid: '',
            openClass: 'expanded',
            animationSpeed: 250
        },

        _create: function() {
            this._super();
            this.$el = this.element;
        },

        _init: function() {
            var storedState = null;
            if (this.options.storageKey) {
                storedState = JSON.parse(localStorage.getItem(this.options.storageKey + this.options.uid));
            }

            this.$trigger = this.$el.find(this.options.trigger);
            this.$container = this.$el.find(this.options.container);

            if (_.isBoolean(this.options.forcedState)) {
                this.options.open = this.options.forcedState;
            } else if (_.isBoolean(storedState)) {
                this.options.open = storedState;
            }

            this.$el.toggleClass(this.options.openClass, this.options.open);
            if (this.options.open) {
                this.$container.show();
            } else {
                this.$container.hide();
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
            if (this.options.breakpoint && $(window).outerWidth() >= this.options.breakpoint) {
                return;
            }
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

                if (self.options.hideSibling) {
                    self._hideSiblings(isOpen);
                }

                $trigger.trigger('collapse:toggle', params);
                mediator.trigger('layout:adjustHeight');

                if (self.options.storageKey) {
                    localStorage.setItem(self.options.storageKey + self.options.uid, isOpen);
                }
            });
        },

        _hideSiblings: function(isOpen) {
            if (isOpen) {
                this.$el.siblings().hide(this.options.animationSpeed);
            } else {
                this.$el.siblings().show(this.options.animationSpeed);
            }
        }
    });

    return 'collapseWidget';
});
