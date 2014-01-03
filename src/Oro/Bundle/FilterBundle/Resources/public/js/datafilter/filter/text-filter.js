/* global define */
define(['jquery', 'underscore', 'oro/translator', 'oro/datafilter/abstract-filter'],
function($, _, __, AbstractFilter) {
    'use strict';

    /**
     * Text grid filter.
     *
     * Triggers events:
     *  - "disable" when filter is disabled
     *  - "update" when filter criteria is changed
     *
     * @export  oro/datafilter/text-filter
     * @class   oro.datafilter.TextFilter
     * @extends oro.datafilter.AbstractFilter
     */
    return AbstractFilter.extend({
        /** @property */
        template: '',

        /**
         * Template for filter criteria
         *
         * @property
         */
        popupCriteriaTemplate: '',

        /** @property */
        templateSelector: '#text-filter-template',

        /** @property */
        simpleTemplateSelector: '#text-filter-simple-template',

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        popupCriteriaTemplateSelector: '#text-filter-popup-criteria-template',

        /**
         * Simple template selector for filter criteria
         *
         * @property
         */
        simplePopupCriteriaTemplateSelector: '#text-filter-popup-criteria-simple-template',

        /**
         * @property {Boolean}
         */
        popupCriteriaShowed: false,

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

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function (options) {
            options = _.pick(options || {}, 'simple');
            _.extend(this, options);

            var templateSrc = $(this.simple ?
                this.simpleTemplateSelector : this.templateSelector).text();
            var popupCriteriaTemplateSrc = $(this.simple ?
                this.simplePopupCriteriaTemplateSelector : this.popupCriteriaTemplateSelector).text();

            this.template = _.template(templateSrc);
            this.popupCriteriaTemplate = _.template(popupCriteriaTemplateSrc);

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
                this.setValue(this._formatRawValue(this._readDOMValue()));
            }
        },

        /**
         * Handle key press on criteria input elements
         *
         * @param {Event} e
         * @protected
         */
        _onReadCriteriaInputKey: function(e) {
            if (e.which === 13) {
                this._hideCriteria();
                this.setValue(this._formatRawValue(this._readDOMValue()));
            }
        },

        /**
         * Handle click on criteria update button
         *
         * @param {Event} e
         * @private
         */
        _onClickUpdateCriteria: function(e) {
            this._hideCriteria();
            this.setValue(this._formatRawValue(this._readDOMValue()));
        },

        /**
         * Handle click on criteria selector
         *
         * @param {Event} e
         * @protected
         */
        _onClickCriteriaSelector: function(e) {
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
                this._hideCriteria();
                this.setValue(this._formatRawValue(this._readDOMValue()));
                e.stopPropagation();
            }
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function () {
            this.setElement(this.template({
                label: this.label,
                showLabel: this.showLabel,
                criteriaHint: this._getCriteriaHint(),
                nullLink: this.nullLink,
                canDisable: !this.simple && this.canDisable
            }));

            this._renderCriteria(this.$(this.criteriaSelector));
            this._clickOutsideCriteriaCallback = _.bind(function(e) {
                if (this.popupCriteriaShowed) {
                    this._onClickOutsideCriteria(e);
                }
            }, this);
            $('body').on('click', this._clickOutsideCriteriaCallback);

            return this;
        },

        /**
         * Render filter criteria popup
         *
         * @param {Object} el
         * @protected
         * @return {*}
         */
        _renderCriteria: function(el) {
            $(el).append(this.popupCriteriaTemplate());
            return this;
        },

        /**
         * Unsubscribe from click on body event
         *
         * @return {*}
         */
        remove: function() {
            $('body').off('click', this._clickOutsideCriteriaCallback);
            AbstractFilter.prototype.remove.call(this);
            return this;
        },

        /**
         * Show criteria popup
         *
         * @protected
         */
        _showCriteria: function() {
            if (this.simple) {
                return;
            }
            this.$(this.criteriaSelector).show();
            this._focusCriteria();
            this._setButtonPressed(this.$(this.criteriaSelector), true);
            setTimeout(_.bind(function() {
                this.popupCriteriaShowed = true;
            }, this), 100);
        },

        /**
         * Hide criteria popup
         *
         * @protected
         */
        _hideCriteria: function() {
            if (this.simple) {
                return;
            }
            this.$(this.criteriaSelector).hide();
            this._setButtonPressed(this.$(this.criteriaSelector), false);
            setTimeout(_.bind(function() {
                this.popupCriteriaShowed = false;
            }, this), 100);
        },

        /**
         * Focus filter criteria input
         *
         * @protected
         */
        _focusCriteria: function() {
            this.$(this.criteriaSelector + ' input').focus().select();
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
         * @inheritDoc
         */
        _onValueUpdated: function(newValue, oldValue) {
            AbstractFilter.prototype._onValueUpdated.apply(this, arguments);
            this._updateCriteriaHint();
        },

        /**
         * Updates criteria hint element with actual criteria hint value
         *
         * @protected
         * @return {*}
         */
        _updateCriteriaHint: function() {
            this.$(this.criteriaHintSelector).html(this._getCriteriaHint());
            return this;
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         * @protected
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            return value.value ? '"' + value.value + '"': this.placeholder;
        }
    });
});
