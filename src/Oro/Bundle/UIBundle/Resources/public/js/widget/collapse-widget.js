define(['jquery', 'oroui/js/mediator', 'underscore', 'jquery-ui'], function($, mediator, _) {
    'use strict';

    var localStorage = window.localStorage;

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            uid: '',
            group: '',
            storageKey: '',
            openClass: 'expanded',
            forcedState: null,
            closeClass: 'collapsed',
            keepState: false,
            open: true,
            hideSibling: false,
            checkOverflow: false,
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

            if (_.isBoolean(this.options.forcedState)) {
                this.options.open = this.options.forcedState;
            } else if (_.isBoolean(storedState)) {
                this.options.open = storedState;
            }

            this.$el.addClass('init');

            this._setElements();
            this._initEvents();
            this._setState(this.options.open);

            if (this.options.open) {
                mediator.trigger('scrollable-table:reload');
            }
        },

        _destroy: function() {
            if (!this.options.keepState) {
                this._setState(true, true);
            }
            this.$el.removeClass('init');
            this._off(this.$trigger, 'click');
            mediator.off(null, null, this);
            this._super();
        },

        _setElements: function() {
            this.$trigger = this.$el.find(this.options.trigger);
            this.$container = this.$el.find(this.options.container);
        },

        _initEvents: function() {
            this._on(this.$trigger, {
                click: this._toggle
            });

            var group = this.options.group;
            if (group) {
                mediator.on('collapse-group-widgets:' + group + ':setState', this._setState, this);
                mediator.on('collapse-group-widgets:' + group + ':collectStates', this._collectStates, this);
            }
        },

        _isOverflow: function() {
            return this.$container[0].scrollHeight > this.$container[0].clientHeight;
        },

        _setState: function(isOpen, isDestroy) {
            this.options.open = isOpen;

            if (this.$container.is(':animated')) {
                this.$container.finish();
            }

            if (this.options.checkOverflow) {
                this.$el.removeClass(this.options.openClass)
                    .removeClass(this.options.closeClass);
                if (!this._isOverflow()) {
                    // do nothing
                    return;
                }
            }

            this._applyStateOnEl(isOpen);
            this._applyStateOnContainer(isOpen);
            this._applyStateOnSiblings(isOpen, isDestroy);
            this._applyStateOnTrigger(isOpen);
            this._applyStateOnGroup(isOpen);

            mediator.trigger('layout:adjustHeight');

            if (this.options.storageKey) {
                localStorage.setItem(this.options.storageKey + this.options.uid, isOpen);
            }
        },

        _applyStateOnEl: function(isOpen) {
            this.$el.toggleClass(this.options.openClass, isOpen);
            this.$el.toggleClass(this.options.closeClass, !isOpen);
        },

        _applyStateOnContainer: function(isOpen) {
            if (this.options.animationSpeed) {
                if (isOpen) {
                    this.$container.slideDown(this.options.animationSpeed);
                } else {
                    this.$container.slideUp(this.options.animationSpeed);
                }
            }
        },

        _applyStateOnSiblings: function(isOpen, isDestroy) {
            if (this.options.hideSibling) {
                this._hideSiblings(isOpen && !isDestroy);
            }
        },

        _applyStateOnTrigger: function(isOpen) {
            this.$trigger.trigger('collapse:toggle', {
                isOpen: isOpen,
                $el: this.$el,
                $trigger: this.$trigger,
                $container: this.$container
            });
        },

        _applyStateOnGroup: function(isOpen) {
            if (this.options.group) {
                mediator.trigger('collapse-group:' + this.options.group + ':setState', isOpen);
            }
        },

        _collectStates: function(states) {
            if (this.options.open) {
                states.expanded++;
            } else {
                states.collapsed++;
            }
        },

        _toggle: function(event) {
            var $trigger = $(event.currentTarget);

            if ($trigger.attr('href')) {
                event.preventDefault();
            }

            this._setState(!this.options.open);
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
