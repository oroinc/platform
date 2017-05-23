define(['jquery', 'oroui/js/mediator', 'underscore', 'jquery-ui'], function($, mediator, _) {
    'use strict';

    var localStorage = window.localStorage;

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            globalTrigger: null,
            hideSibling: false,
            storageKey: '',
            open: true,
            forcedState: null,
            uid: '',
            openClass: 'expanded',
            overflowClass: 'overflows',
            toggleClassesOnly: false,
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
            this._bindGlobalTrigger();

            this.$el.toggleClass(this.options.openClass, this.options.open);
            this.$el.toggleClass(
                this.options.overflowClass,
                this.$container[0].scrollHeight > this.$container[0].clientHeight
            );

            if (this.options.toggleClassesOnly) {
                return;
            }

            if (this.options.open) {
                this.$container.show();
                mediator.trigger('scrollable-table:reload');
            } else {
                this.$container.hide();
            }
        },

        _bindGlobalTrigger: function() {
            var self = this;
            var globalTrigger = this.options.globalTrigger;
            var triggerBind = $(globalTrigger).attr('data-event-bind');
            var animationSpeed = this.options.animationSpeed;

            if (globalTrigger) {
                //Bind onClick event(toggle expanded class) only for the first instance
                if (!triggerBind) {
                    $(globalTrigger).on('click', function() {
                        $(this).toggleClass('expanded');
                    });
                    $(globalTrigger).toggleClass('expanded');
                    $(globalTrigger).attr('data-event-bind', true);
                }
                //Bind onClick event(animation for container) for each items
                $(globalTrigger).on('click', function() {
                    if (!$(this).hasClass('expanded')) {
                        self.$container.slideUp(animationSpeed);
                        self._setState(false);
                    } else {
                        self.$container.slideDown(animationSpeed);
                        self._setState(true);
                    }
                });
            }
        },

        _destroy: function() {
            this._off(this.$trigger, 'click');
            this.$el.removeClass('init');
            if (!this.options.toggleClassesOnly) {
                this.$container.css('display', '');
                this._setState(true, true);
            }
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
            this.$el.toggleClass(
                this.options.overflowClass,
                this.$container[0].scrollHeight > this.$container[0].clientHeight
            );

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

            var self = this;
            if (!this.options.toggleClassesOnly) {
                if (!this.$container.is(':animated')) {
                    this.$container.slideToggle(this.options.animationSpeed, function() {
                        self._setState($(this).is(':visible'));
                    });
                }

                return false;
            }

            this.options.open = !this.options.open;
            self._setState(this.options.open);
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
