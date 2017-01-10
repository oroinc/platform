define(['jquery', 'oroui/js/mediator', 'underscore', 'jquery-ui'], function($, mediator, _) {
    'use strict';

    var localStorage = window.localStorage;

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            hideSibling: false,
            storageKey: '',
            open: true,
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

            this.$el.addClass('init');

            this._initEvents();

            this.$el.toggleClass(this.options.openClass, this.options.open);
            if (this.options.open) {
                this.$container.show();
            } else {
                this.$container.hide();
            }
        },

        _destroy: function() {
            this._setState(true, true);
            this.$el.removeClass('init');
            this.$container.css('display', '');
            this._off(this.$trigger, 'click');
            this._super();
        },

        _initEvents: function() {
            this._on(this.$trigger, {
                'click': this._toggle
            });
        },

        _setState: function(isOpen, isDestroy) {
            var params = {
                isOpen: isOpen,
                $el: this.$el,
                $trigger: this.$trigger,
                $container: this.$container
            };

            this.$el.toggleClass(this.options.openClass, isOpen);

            if (this.options.hideSibling) {
                this._hideSiblings(isOpen && !isDestroy);
            }

            this.$trigger.trigger('collapse:toggle', params);
            mediator.trigger('layout:adjustHeight');

            if (this.options.storageKey) {
                localStorage.setItem(this.options.storageKey + this.options.uid, isOpen);
            }
        },

        _toggle: function(event) {
            var $trigger = $(event.currentTarget);

            if ($trigger.attr('href')) {
                event.preventDefault();
            }

            if (this.$container.is(':animated')) {
                return false;
            }

            var self = this;
            this.$container.slideToggle(this.options.animationSpeed, function() {
                self._setState($(this).is(':visible'));
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
