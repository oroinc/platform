define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const persistentStorage = require('oroui/js/persistent-storage');
    require('jquery-ui/widget');

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

        _triggerAriaHidden: void 0,

        _create: function() {
            this._super();
            this.$el = this.element;
        },

        _init: function() {
            let storedState = null;
            if (this.options.storageKey) {
                storedState = JSON.parse(persistentStorage.getItem(this.options.storageKey + this.options.uid));
            }

            if (_.isBoolean(this.options.forcedState)) {
                this.options.open = this.options.forcedState;
            } else if (_.isBoolean(storedState)) {
                this.options.open = storedState;
            }

            this.$el.addClass('init');

            this._setElements();
            this._initEvents();
            this._updateState();

            mediator.trigger('layout:adjustHeight');

            if (this.options.open) {
                mediator.trigger('scrollable-table:reload');
            }
        },

        _destroy: function() {
            if (!this.options.keepState) {
                this._setState(true, true);
            }

            this.$el.removeClass(this.options.openClass + ' init ' + this.options.closeClass);
            this._off(this.$trigger, 'click');

            this.$trigger.removeAttr('aria-expanded');
            this.$trigger.removeAttr('aria-controls');

            if (this._triggerAriaHidden !== void 0) {
                this.$trigger.attr('aria-hidden', this._triggerAriaHidden);
            }

            if (!this.elementHasLabel(this.$trigger)) {
                this.$trigger.removeAttr('aria-label');
            }

            mediator.off(null, null, this);
            this._super();
        },

        _setElements: function() {
            let id = _.uniqueId('collapse-');

            this.$trigger = this.$el.find(this.options.trigger);
            this.$container = this.$el.find(this.options.container);

            if (this.$container.attr('id')) {
                id = this.$container.attr('id');
            }

            this.$container.attr('id', id);
            this.$trigger.attr('aria-controls', id);

            if (this.$trigger.attr('aria-hidden') !== void 0) {
                this._triggerAriaHidden = this.$trigger.attr('aria-hidden');
                this.$trigger.removeAttr('aria-hidden');
            }
        },

        _initEvents: function() {
            this._on(this.$trigger, {
                click: this._toggle
            });

            mediator.on('layout:reposition', _.debounce(this._updateState.bind(this), 0));

            const group = this.options.group;
            if (group) {
                mediator.on('collapse-group-widgets:' + group + ':setState', this._setState, this);
                mediator.on('collapse-group-widgets:' + group + ':collectStates', this._collectStates, this);
            }
        },

        _isOverflow: function() {
            return this.$container[0].scrollHeight > this.$container[0].clientHeight;
        },

        _updateState: function() {
            this._setState(this.options.open);
        },

        _setState: function(isOpen, isDestroy) {
            this.options.open = isOpen;

            if (this.$container.is(':animated')) {
                this.$container.finish();
            }

            if (this.options.checkOverflow) {
                this.$el.removeClass(this.options.openClass + ' ' + this.options.closeClass);
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

            if (this.options.storageKey) {
                persistentStorage.setItem(this.options.storageKey + this.options.uid, isOpen);
            }
        },

        _applyStateOnEl: function(isOpen) {
            this.$el.toggleClass(this.options.openClass, isOpen);
            this.$el.toggleClass(this.options.closeClass, !isOpen);
            this.$el.attr('aria-expanded', isOpen);
        },

        _applyStateOnContainer: function(isOpen) {
            if (this.options.animationSpeed) {
                this.$container[isOpen ? 'slideDown' : 'slideUp'](
                    this.options.animationSpeed,
                    () => this.$container.attr('aria-hidden', !isOpen)
                );
            } else {
                this.$container.attr('aria-hidden', !isOpen);
            }
        },

        _applyStateOnSiblings: function(isOpen, isDestroy) {
            if (this.options.hideSibling) {
                this._hideSiblings(isOpen && !isDestroy);
            }
        },

        _applyStateOnTrigger: function(isOpen) {
            this.$trigger
                .attr('aria-expanded', isOpen)
                .trigger('collapse:toggle', {
                    isOpen: isOpen,
                    $el: this.$el,
                    $trigger: this.$trigger,
                    $container: this.$container
                });

            if (!this.elementHasLabel(this.$trigger)) {
                this.$trigger.attr('aria-label', __(`oro.ui.collapse.${isOpen ? 'less' : 'more'}`));
            }
        },

        elementHasLabel: function($el) {
            return $el.text().trim().length > 0;
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
            if (event.currentTarget.getAttribute('href')) {
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
