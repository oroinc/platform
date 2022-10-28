define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const persistentStorage = require('oroui/js/persistent-storage');
    const mediator = require('oroui/js/mediator');
    const Util = require('bootstrap-util');
    require('bootstrap-collapse');

    const NAME = 'collapse';
    const COLLAPSED = 'collapsed';
    const EXPANDED = 'expanded';
    const DATA_KEY = 'bs.collapse';
    const EVENT_KEY = '.' + DATA_KEY;
    const DATA_API_KEY = '.data-api';
    const Collapse = $.fn.collapse.Constructor;
    const original = _.pick(Collapse.prototype, 'show', 'hide', '_getConfig');
    const originalDefault = Collapse.Default;
    const Event = {
        SHOW: 'show' + EVENT_KEY,
        SHOWN: 'shown' + EVENT_KEY,
        HIDE: 'hide' + EVENT_KEY,
        HIDDEN: 'hidden' + EVENT_KEY,
        CLICK_DATA_API: 'click' + EVENT_KEY + DATA_API_KEY
    };
    const ClassName = {
        SHOW: 'show',
        COLLAPSE: 'collapse',
        COLLAPSING: 'collapsing',
        COLLAPSED: 'collapsed',
        OVERFLOW: 'overflow-mode',
        NOTRANSITION: 'no-transition'
    };
    const ExtendedDefaultType = {
        stateId: 'string',
        hideClass: 'string',
        triggerHideClass: 'string',
        triggerIconHideClass: 'string',
        showClass: 'string',
        triggerShowClass: 'string',
        triggerIconShowClass: 'string',
        checkOverflow: 'boolean'
    };
    const ExtendedDefault = {
        ...originalDefault,
        showClass: '',
        triggerShowClass: '',
        triggerIconShowClass: '',
        hideClass: '',
        triggerHideClass: '',
        triggerIconHideClass: '',
        stateId: '',
        checkOverflow: false
    };

    Collapse.prototype.show = function() {
        this._isOpen = true;
        this._triggerArray.forEach(el => {
            this.captionUpdate(this._isOpen, $(el));
        });

        $(this._element).removeClass(this._config.hideClass).addClass(this._config.showClass);
        this.saveState(this._isOpen);

        return original.show.call(this);
    };

    Collapse.prototype.hide = function() {
        this._isOpen = false;
        this._triggerArray.forEach(el => {
            this.captionUpdate(this._isOpen, $(el));
        });

        $(this._element).removeClass(this._config.showClass).addClass(this._config.hideClass);
        this.saveState(this._isOpen);

        return original.hide.call(this);
    };

    /**
     * Update trigger element attributes according to state
     *
     * @param {boolean} state
     * @param {Query.Element} $el
     */
    Collapse.prototype.captionUpdate = function(state, $el) {
        const prefix = state ? EXPANDED : COLLAPSED;
        const $textEl = $el.find('[data-text]');
        const $iconEl = $el.find('[data-icon]');

        if (this._config[`${prefix}Title`]) {
            $el.attr('title', this._config[`${prefix}Title`]);
        }

        if (this._config[`${prefix}AriaLabel`]) {
            $el.attr('aria-label', this._config[`${prefix}AriaLabel`]);
        }

        if (this._config[`${prefix}Text`]) {
            $textEl.text(this._config[`${prefix}Text`]);
        }

        if (prefix === EXPANDED) {
            $el.removeClass(this._config.triggerHideClass).addClass(this._config.triggerShowClass);
            $iconEl.removeClass(this._config.triggerIconHideClass).addClass(this._config.triggerIconShowClass);
        } else {
            $el.removeClass(this._config.triggerShowClass).addClass(this._config.triggerHideClass);
            $iconEl.removeClass(this._config.triggerIconShowClass).addClass(this._config.triggerIconHideClass);
        }
    };

    /**
     * @param {boolean} state
     */
    Collapse.prototype.saveState = function(state) {
        if (this._config.stateId) {
            persistentStorage.setItem(this._config.stateId, state);
        }
    };

    Collapse.prototype.restoreState = function() {
        const state = persistentStorage.getItem(this._config.stateId);
        const visibleTriggers = this._triggerArray.filter(el => $(el).is(':visible')).length;

        // 1. Skip if triggers are invisible.
        //    The triggers might be hidden by default, for example collapses with overflow option.
        // 2. The storage item exists.
        if (visibleTriggers && state !== null) {
            if (JSON.parse(state)) {
                this.show();
            } else {
                this.hide();
            }
        }
    };

    Collapse.prototype.overflow = function() {
        // 1. checkOverflow option is disabled
        // 2. "_isOpen" means that all content is shown so can't identify if collapse overflows
        if (this._config.checkOverflow === false || this._isOpen === true) {
            return;
        }

        $(this._element).toggleClass(ClassName.OVERFLOW, this._isOverflow());
        // Restore a state only once
        if (this._isOverflow() && this._isOpen === void 0) {
            this.restoreState();
        }
    };

    /**
     * @param {Object} config
     * @private
     */
    Collapse.prototype._getConfig = function(config) {
        config = original._getConfig.call(this, config);
        config = {
            ...ExtendedDefault,
            ...config
        };
        Util.typeCheckConfig(NAME, config, ExtendedDefaultType);

        return config;
    };

    /**
     * Determines if the collapsible element is overflowing its bounds horizontally
     * @returns {boolean}
     * @private
     */
    Collapse.prototype._isOverflow = function() {
        const classes = `${ClassName.SHOW} ${ClassName.NOTRANSITION}`;

        // Show collapse element and disable animation
        $(this.element).addClass(classes);

        const isOverflow = this._element.scrollHeight > this._element.clientHeight;

        $(this.element).removeClass(classes);

        return isOverflow;
    };

    /**
     * Extend Default collapse property
     * @static
     */
    Object.defineProperty(Collapse, 'Default', {
        get() {
            return ExtendedDefault;
        }
    });
    $(document)
        .on(Event.SHOWN, event => {
            mediator.trigger('content:shown', $(event.target));
        })
        .on(Event.HIDDEN, event => {
            mediator.trigger('content:hidden', $(event.target));
        })
        .on('initLayout', function(event) {
            $(event.target).find('[data-toggle="collapse"]').each(function(index, el) {
                const $collapse = $($(el).attr('data-target') || $(el).attr('href'));
                const state = persistentStorage.getItem($collapse.attr('data-sate-id'));

                if ($collapse.data('check-overflow')) {
                    $collapse.attr('data-toggle', false);
                    $collapse.collapse('overflow');
                } else if (state !== null) {
                    $collapse.collapse(
                        JSON.parse(state) ? 'show' : 'hide'
                    );
                }
            });
        })
        // expand a collapse to show validation message within
        .on('validate-element', '.collapse:hidden', event => $(event.currentTarget).collapse('show'));

    mediator.on('layout:reposition', _.debounce(() => {
        $(document).find('[data-toggle="collapse"]').each(function(index, el) {
            const $collapse = $($(el).attr('data-target') || $(el).attr('href'));

            if ($collapse.data('check-overflow')) {
                $collapse.collapse('overflow');
            }
        });
    }, 40));
});
