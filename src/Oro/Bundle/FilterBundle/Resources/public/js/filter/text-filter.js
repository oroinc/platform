/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', './abstract-filter'
    ], function ($, _, __, AbstractFilter) {
    'use strict';

    /**
     * Text grid filter.
     *
     * Triggers events:
     *  - "disable" when filter is disabled
     *  - "update" when filter criteria is changed
     *
     * @export  orofilter/js/filter/text-filter
     * @class   orofilter.filter.TextFilter
     * @extends orofilter.filter.AbstractFilter
     */
    return AbstractFilter.extend({
        wrappable: true,

        wrapperTemplate: '',

        wrapperTemplateSelector: '#filter-wrapper-template',

        /**
         * Template selector for filter criteria
         *
         * @property
         */
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
         * Element enclosing a criteria dropdown
         *
         * @property {string|jQuery|HTMLElement}
         */
        limitCriteriaTo: '#container',

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
            'keyup input': '_onReadCriteriaInputKey',
            'keydown [type="text"]': '_preventEnterProcessing',
            'click .filter-update': '_onClickUpdateCriteria',
            'click .filter-criteria-selector': '_onClickCriteriaSelector',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'click .reset-filter': '_onClickResetFilter'
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function (options) {
            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            AbstractFilter.prototype.initialize.apply(this, arguments);
        },

        /**
         * Makes sure the criteria popup dialog is closed
         */
        ensurePopupCriteriaClosed: function () {
            if (this.popupCriteriaShowed) {
                this._hideCriteria();
                this.applyValue();
            }
        },

        /**
         * Handle key press on criteria input elements
         *
         * @param {Event} e
         * @protected
         */
        _onReadCriteriaInputKey: function (e) {
            if (e.which === 13) {
                this._hideCriteria();
                this.applyValue();
            }
        },

        /**
         * Handle click on criteria update button
         *
         * @param {Event} e
         * @private
         */
        _onClickUpdateCriteria: function (e) {
            this._hideCriteria();
            this.applyValue();
        },

        /**
         * Handle click on criteria selector
         *
         * @param {Event} e
         * @protected
         */
        _onClickCriteriaSelector: function (e) {
            e.stopPropagation();
            $('body').trigger('click');
            if (!this.popupCriteriaShowed) {
                this._showCriteria();
            } else {
                this._hideCriteria();
            }
        },

        /**
         * Handle click on criteria close button
         *
         * @private
         */
        _onClickCloseCriteria: function () {
            this._hideCriteria();
            this._updateDOMValue();
        },

        /**
         * Handle click on filter disabler
         *
         * @param {Event} e
         */
        _onClickDisableFilter: function (e) {
            e.preventDefault();
            this.disable();
        },

        /**
         * Handle click outside of criteria popup to hide it
         *
         * @param {Event} e
         * @protected
         */
        _onClickOutsideCriteria: function (e) {
            var elem = this.$(this.criteriaSelector);

            if (elem.get(0) !== e.target && !elem.has(e.target).length) {
                this._hideCriteria();
                this.applyValue();
                e.stopPropagation();
            }
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function () {
            var $filter = $(this.template());
            this._wrap($filter);
            return this;
        },

        _wrap: function ($filter) {
            this.$el.append($filter);
        },

        /**
         * Unsubscribe from click on body event
         *
         * @return {*}
         */
        remove: function () {
            $('body').off('click', this._clickOutsideCriteriaCallback);
            AbstractFilter.prototype.remove.call(this);
            return this;
        },

        /**
         * Show criteria popup
         *
         * @protected
         */
        _showCriteria: function () {
            this.$(this.criteriaSelector).show();
            this._alignCriteria();
            this._focusCriteria();
            this._setButtonPressed(this.$(this.criteriaSelector), true);
            setTimeout(_.bind(function () {
                this.popupCriteriaShowed = true;
            }, this), 100);
        },

        /**
         * Check if criteria dropdown fits viewport, if not - applies margin shift
         *
         * @private
         */
        _alignCriteria: function () {
            var $container = $(this.limitCriteriaTo),
                $criteria = this.$(this.criteriaSelector),
                shift = $container.prop('clientWidth') + $container.offset().left -
                    this.$el.offset().left - $criteria.outerWidth();
            $criteria.css('margin-left', shift < 0 ? shift : 0);
        },

        /**
         * Hide criteria popup
         *
         * @protected
         */
        _hideCriteria: function () {
            this.$(this.criteriaSelector).hide();
            this._setButtonPressed(this.$(this.criteriaSelector), false);
            setTimeout(_.bind(function () {
                this.popupCriteriaShowed = false;
            }, this), 100);
        },

        /**
         * Focus filter criteria input
         *
         * @protected
         */
        _focusCriteria: function () {
            this.$(this.criteriaSelector + ' input').focus().select();
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function (value) {
            this._setInputValue(this.criteriaValueSelectors.value, value.value);
            return this;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function () {
            return {
                value: this._getInputValue(this.criteriaValueSelectors.value)
            };
        },

        /**
         * @inheritDoc
         */
        _onValueUpdated: function (newValue, oldValue) {
            AbstractFilter.prototype._onValueUpdated.apply(this, arguments);
            this._updateCriteriaHint();
        },

        /**
         * Updates criteria hint element with actual criteria hint value
         *
         * @protected
         * @return {*}
         */
        _updateCriteriaHint: function () {
            this.$(this.criteriaHintSelector)
                .html(_.escape(this._getCriteriaHint()))
                .closest('.filter-criteria-selector')
                .toggleClass('filter-default-value', this.isEmptyValue());
            return this;
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         * @protected
         */
        _getCriteriaHint: function () {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            return value.value ? '"' + value.value + '"' : this.placeholder;
        }
    });
});
