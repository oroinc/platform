define(function(require, exports, module) {
    'use strict';

    const wrapperTemplate = require('tpl-loader!orofilter/templates/filter/filter-wrapper.html');
    const template = require('tpl-loader!orofilter/templates/filter/text-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const EmptyFilter = require('oro/filter/empty-filter');
    const tools = require('oroui/js/tools');
    const mediator = require('oroui/js/mediator');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    const KEYBOARD_CODES = require('oroui/js/tools/keyboard-key-codes').default;

    let config = require('module-config').default(module.id);
    config = _.extend({
        notAlignCriteria: tools.isMobile()
    }, config);

    require('jquery-ui/tabbable');

    /**
     * Text grid filter.
     *
     * Triggers events:
     *  - "disable" when filter is disabled
     *  - "update" when filter criteria is changed
     *  - "updateCriteriaClick" when update button clicked
     *
     * @export  oro/filter/text-filter
     * @class   oro.filter.TextFilter
     * @extends oro.filter.EmptyFilter
     */
    const TextFilter = EmptyFilter.extend({
        wrappable: true,

        notAlignCriteria: config.notAlignCriteria,

        wrapperTemplate: wrapperTemplate,
        wrapperTemplateSelector: '#filter-wrapper-template',

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        template: template,
        templateSelector: '#text-filter-template',

        /**
         * Selector to element of criteria hint
         *
         * @property {String}
         */
        criteriaHintSelector: '.filter-criteria-hint',

        /**
         * Selector to criteria popup container
         *
         * @property {String}
         */
        criteriaSelector: '.filter-criteria',

        /**
         * Selectors for filter criteria elements
         *
         * @property {Object}
         */
        criteriaValueSelectors: {
            value: 'input[name="value"]',
            nested: {
                end: 'input'
            }
        },

        /**
         * View events
         *
         * @property {Object}
         */
        events: {
            // Exclude from selection an auxiliary input inside select2 component
            'keydown input:not(.select2-focusser)': '_onReadCriteriaInputKey',
            'click .filter-update': '_onClickUpdateCriteria',
            'keydown .filter-update': '_onKeydownUpdateCriteria',
            'keydown .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'focusin .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'focusout .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'keydown .filter-criteria': 'onKeyDownCriteria',
            'click .filter-criteria-selector': '_onClickCriteriaSelector',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'change input': '_onValueChanged'
        },

        listen: {
            'layout:reposition mediator': '_onLayoutReposition'
        },

        /**
         * @inheritdoc
         */
        constructor: function TextFilter(options) {
            TextFilter.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            TextFilter.__super__.initialize.call(this, options);
        },

        /**
         * Makes sure the criteria popup dialog is closed
         */
        ensurePopupCriteriaClosed: function() {
            if (this.popupCriteriaShowed) {
                this._applyValueAndHideCriteria();
            }
        },

        /**
         * Handle key press on criteria input elements
         *
         * @param {Event} e
         * @protected
         */
        _onReadCriteriaInputKey: function(e) {
            this._onValueChanged();

            if (e.which !== 13) {
                return;
            }

            e.preventDefault();

            if (!this._isValid()) {
                return;
            }

            this._applyValueAndHideCriteria();
        },

        /**
         * Handle click on criteria update button
         *
         * @param {Event} e
         * @private
         */
        _onClickUpdateCriteria: function(e) {
            if (!this._isValid()) {
                e.stopImmediatePropagation();
                return;
            }

            this.trigger('updateCriteriaClick', this);
            this._applyValueAndHideCriteria();
        },

        /**
         * Handle keydown on criteria update button
         *
         * @param {Event} e
         * @private
         */
        _onKeydownUpdateCriteria(e) {
            if (e.keyCode === KEYBOARD_CODES.ENTER || e.keyCode === KEYBOARD_CODES.SPACE) {
                this._onClickUpdateCriteria(e);
            }
        },

        /**
         * Handles min_length and max_length text filter option.
         *
         * @returns {boolean}
         * @private
         */
        _isValid: function() {
            if (typeof this.min_length !== 'undefined' && this._readDOMValue().value.length < this.min_length) {
                this._showMinLengthWarning();
                return false;
            }

            if (typeof this.max_length !== 'undefined' && this._readDOMValue().value.length > this.max_length) {
                this._showMaxLengthWarning();
                return false;
            }

            return true;
        },

        /**
         * @inheritdoc
         */
        _isDOMValueChanged: function() {
            const thisDOMValue = this._readDOMValue();
            return (
                !_.isUndefined(thisDOMValue.value) &&
                !_.isNull(thisDOMValue.value) &&
                !_.isEqual(this.value, thisDOMValue)
            );
        },

        /**
         * @private
         */
        _showMinLengthWarning: function() {
            mediator.execute(
                'showFlashMessage',
                'warning',
                __('oro.filter.warning.min_length', {min_length: this.min_length})
            );
        },

        /**
         * @private
         */
        _showMaxLengthWarning: function() {
            mediator.execute(
                'showFlashMessage',
                'warning',
                __('oro.filter.warning.max_length', {max_length: this.max_length})
            );
        },

        /**
         * Handle click on criteria close button
         *
         * @private
         */
        _onClickCloseCriteria: function() {
            this._hideCriteria();
            this._updateDOMValue();
        },

        /**
         * Handle click on filter disabler
         *
         * @param {Event} e
         */
        _onClickDisableFilter: function(e) {
            e.preventDefault();
            this.disable();
        },

        /**
         * Handle click outside of criteria popup to hide it
         *
         * @param {Event} e
         * @protected
         */
        _onClickOutsideCriteria: function(e) {
            const elem = this.$(this.criteriaSelector);

            if (elem.get(0) !== e.target && !elem.has(e.target).length) {
                this._applyValueAndHideCriteria();
            }
        },

        _onLayoutReposition: function() {
            if (this.popupCriteriaShowed) {
                this._alignCriteria();
            }
        },

        /**
         * @protected
         */
        _applyValueAndHideCriteria: function() {
            if (this.autoClose !== false) {
                this._hideCriteria();
            }
            if (this._isValid()) {
                this.applyValue();
            }
        },

        resetFlags() {
            this.popupCriteriaShowed = false;
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function() {
            this.resetFlags();
            const $filter = $(this.template({
                renderMode: this.renderMode
            }));
            this._wrap($filter);
            return this;
        },

        /**
         * Renders filter's wrapper, (a button and a dropdown container e.g.)
         *
         * @param {Element|jQuery|string}  $filter
         * @private
         */
        _wrap: function($filter) {
            this._appendFilter($filter);
        },

        /**
         * Append filter to its place
         *
         * @param {Element|jQuery|string} $filter
         * @private
         */
        _appendFilter: function($filter) {
            this.$el.append($filter);
        },

        /**
         * Show criteria popup
         *
         * @protected
         */
        _showCriteria: function() {
            $(document).trigger('clearMenus'); // hides all opened dropdown menus
            this.$(this.criteriaSelector)
                .removeClass('criteria-hidden')
                .addClass('criteria-visible')
                .attr('tabindex', -1);
            this._alignCriteria();
            this._setButtonPressed(this.$(this.criteriaSelector), true);
            if (this.autoClose !== false) {
                this._focusCriteriaValue();
            }
            this.trigger('showCriteria', this);
            setTimeout(() => {
                this.popupCriteriaShowed = true;
            }, 100);
        },

        /**
         * Check if criteria dropdown fits viewport, if not - applies margin shift
         *
         * @private
         */
        _alignCriteria: function() {
            if (this.notAlignCriteria) {
                // no need to align criteria on mobile version, it is aligned over CSS
                return;
            }
            const $container = this.$el.closest('.filter-box');
            if (!$container.length) {
                return;
            }
            const $dropdown = this.$(this.criteriaSelector);
            $dropdown.css('margin-inline-start', 'auto');

            const rect = $dropdown.get(0).getBoundingClientRect();
            const rectInlineEnd = rect[_.isRTL() ? 'left' : 'right'];

            const containerRect = $container.get(0).getBoundingClientRect();
            const containerRectInlineEnd = containerRect[_.isRTL() ? 'left' : 'right'];

            let shift = rectInlineEnd - containerRectInlineEnd;

            if (!_.isRTL() && shift > 0) {
                /**
                 * reduce shift to avoid overlaping left edge of container
                 */
                shift -= Math.max(0, containerRect.left - (rect.left - shift));
                $dropdown.css('margin-inline-start', -shift);
            }

            if (_.isRTL() && shift < 0) {
                $dropdown.css('margin-inline-start', shift);
            }
        },

        /**
         * Hide criteria popup
         *
         * @protected
         */
        _hideCriteria: function() {
            this.$(this.criteriaSelector)
                .removeClass('criteria-visible')
                .addClass('criteria-hidden')
                .removeAttr('tabindex');
            this._setButtonPressed(this.$(this.criteriaSelector), false);
            this.trigger('hideCriteria', this);
            setTimeout(() => {
                if (!this.disposed) {
                    this.popupCriteriaShowed = false;
                }
            }, 100);
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.value, value.value);
            return this;
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.criteriaValueSelectors.value)
            };
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         * @protected
         */
        _getCriteriaHint: function(...args) {
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();

            if (!value.value) {
                return this.placeholder;
            }

            return '"' + value.value + '"';
        },

        onKeyDownCriteria(e) {
            if (
                e.keyCode === KEYBOARD_CODES.ENTER &&
                !this.getCriteria().is(':visible')
            ) {
                this.focusCriteriaToggler();
            } else if (
                e.keyCode === KEYBOARD_CODES.ENTER &&
                this.$(this.criteriaSelector).is(':animated')
            ) {
                this.$(this.criteriaSelector).done(() => {
                    if (
                        !this.getCriteria().is(':visible') &&
                        document.activeElement.isSameNode(e.target)
                    ) {
                        this.focusCriteriaToggler();
                    }
                });
            } else if (this.isDropdownRenderMode()) {
                manageFocus.preventTabOutOfContainer(e, e.currentTarget);
            }
        },

        _triggerEventOnCriteriaToggle(e) {
            this.trigger(`${e.type}OnToggle`, e, this);
        },

        _focusCriteriaValue() {
            this.getCriteriaValueFieldToFocus().focus().select();
        },

        /**
         * @return {jQuery}
         */
        getCriteriaValueFieldToFocus() {
            if (this.criteriaValueSelectors === void 0) {
                return $();
            } else if (typeof this.criteriaValueSelectors.value === 'string') {
                return this.$(`${this.criteriaSelector} ${this.criteriaValueSelectors.value}`);
            }

            return this.$(`${this.criteriaSelector} ${this.criteriaValueSelectors.value.start}`);
        },

        getCriteriaSelector() {
            return this.$('.filter-criteria-selector');
        },

        getCriteria() {
            return this.$(this.criteriaSelector);
        },

        focusCriteriaToggler() {
            this.getCriteriaSelector().trigger('focus');
        }
    });

    return TextFilter;
});
