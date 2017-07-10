define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    './empty-filter',
    'oroui/js/tools',
    'oroui/js/mediator',
    'module'
], function($, _, __, EmptyFilter, tools, mediator, module) {
    'use strict';
    var config = module.config();
    config = _.extend({
        notAlignCriteria: tools.isMobile()
    }, config);

    var TextFilter;

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
    TextFilter = EmptyFilter.extend({
        wrappable: true,

        notAlignCriteria: config.notAlignCriteria,

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
            'click .disable-filter': '_onClickDisableFilter'
        },

        listen: {
            'layout:reposition mediator': '_onLayoutReposition'
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

            TextFilter.__super__.initialize.apply(this, arguments);
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
            if (e.which !== 13) {
                return;
            }

            if (!this._hasMinimumLength()) {
                this._showMinLengthWarning();
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

            if (!this._hasMinimumLength()) {
                this._showMinLengthWarning();
                e.stopImmediatePropagation();
                return;
            }

            this.trigger('updateCriteriaClick', this);
            this._applyValueAndHideCriteria();
        },

        /**
         * Handles min_length text filter option.
         * 0 is default value which means any length is fine.
         *
         * @returns {boolean}
         * @private
         */
        _hasMinimumLength: function() {
            if (typeof this.min_length === 'undefined') {
                return true;
            }

            var enoughCharacters =  this._readDOMValue().value.length >= this.min_length;
            var noCharacters = this._readDOMValue().value.length === 0;

            return this.min_length === 0 || enoughCharacters || noCharacters;
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
            var elem = this.$(this.criteriaSelector);

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
            this._hideCriteria();
            if (this._hasMinimumLength()) {
                this.applyValue();
            }
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function() {
            var $filter = $(this.template());
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
            this.trigger('showCriteria', this);
            this.$(this.criteriaSelector).css('visibility', 'visible');
            this._alignCriteria();
            this._focusCriteria();
            this._setButtonPressed(this.$(this.criteriaSelector), true);
            setTimeout(_.bind(function() {
                this.popupCriteriaShowed = true;
            }, this), 100);
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
            var $container = this.$el.closest('.filter-box');
            if (!$container.length) {
                return;
            }
            var $dropdown = this.$(this.criteriaSelector);
            $dropdown.css('margin-left', 'auto');
            var rect = $dropdown.get(0).getBoundingClientRect();
            var containerRect = $container.get(0).getBoundingClientRect();
            var shift = rect.right - containerRect.right;
            if (shift > 0) {
                /**
                 * reduce shift to avoid overlaping left edge of container
                 */
                shift -= Math.max(0, containerRect.left - (rect.left - shift));
                $dropdown.css('margin-left', -shift);
            }
        },

        /**
         * Hide criteria popup
         *
         * @protected
         */
        _hideCriteria: function() {
            this.$(this.criteriaSelector).css('visibility', 'hidden');
            this._setButtonPressed(this.$(this.criteriaSelector), false);
            setTimeout(_.bind(function() {
                if (!this.disposed) {
                    this.popupCriteriaShowed = false;
                }
            }, this), 100);
        },

        /**
         * Focus filter criteria input
         *
         * @protected
         */
        _focusCriteria: function() {
            this.$(this.criteriaSelector + ' input[type=text]').focus().select();
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.value, value.value);
            return this;
        },

        /**
         * @inheritDoc
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
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();

            if (!value.value) {
                return this.placeholder;
            }

            return '"' + value.value + '"';
        }
    });

    return TextFilter;
});
