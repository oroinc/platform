define(function(require) {
    'use strict';

    const wrapperTemplate = require('tpl-loader!orofilter/templates/filter/filter-wrapper.html');
    const template = require('tpl-loader!orofilter/templates/filter/none-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const AbstractFilter = require('oro/filter/abstract-filter');

    /**
     * None filter: an empty filter implements 'null object' pattern
     *
     * Triggers events:
     *  - "disable" when filter is disabled
     *
     * @export  oro/filter/none-filter
     * @class   oro.filter.NoneFilter
     * @extends oro.filter.AbstractFilter
     */
    const NoneFilter = AbstractFilter.extend({
        wrappable: true,

        wrapperTemplate: wrapperTemplate,
        wrapperTemplateSelector: '#filter-wrapper-template',

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        template: template,
        templateSelector: '#none-filter-template',

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
         * A value showed as filter's popup hint
         *
         * @property {String}
         */
        popupHint: 'Choose a value first',

        /**
         * View events
         *
         * @property {Object}
         */
        events: {
            'click .filter-criteria-selector': '_onClickCriteriaSelector',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter'
        },

        /**
         * @inheritdoc
         */
        constructor: function NoneFilter(options) {
            NoneFilter.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const opts = _.pick(options || {}, 'popupHint');
            _.extend(this, opts);

            this.label = 'None';
            NoneFilter.__super__.initialize.call(this, options);
        },

        /**
         * Makes sure the criteria popup dialog is closed
         */
        ensurePopupCriteriaClosed: function() {
            if (this.popupCriteriaShowed) {
                this._hideCriteria();
            }
        },

        /**
         * Handle click on criteria selector
         *
         * @param {Event} e
         * @protected
         */
        _onClickCriteriaSelector: function(e) {
            e.stopPropagation();
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
            const elem = this.$(this.criteriaSelector);

            if (elem.get(0) !== e.target && !elem.has(e.target).length) {
                this._hideCriteria();
            }
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function() {
            const $filter = $(this.template({
                popupHint: this._getPopupHint(),
                renderMode: this.renderMode
            }));
            this._wrap($filter);
            if (this.initiallyOpened) {
                this._showCriteria();
            }
            return this;
        },

        _wrap: function($filter) {
            this.$el.append($filter);
        },

        /**
         * Show criteria popup
         *
         * @protected
         */
        _showCriteria: function() {
            this.$(this.criteriaSelector).show();
            this._setButtonPressed(this.$(this.criteriaSelector), true);
            this.trigger('showCriteria', this);
            setTimeout(() => {
                this.popupCriteriaShowed = true;
            }, 100);
        },

        /**
         * Hide criteria popup
         *
         * @protected
         */
        _hideCriteria: function() {
            this.$(this.criteriaSelector).hide();
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
            return this;
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            return {};
        },

        /**
         * Get popup hint value
         *
         * @return {String}
         * @protected
         */
        _getPopupHint: function() {
            return this.popupHint ? this.popupHint : this.popupHint;
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         * @protected
         */
        _getCriteriaHint: function() {
            return this.criteriaHint ? this.criteriaHint : this.placeholder;
        }
    });

    return NoneFilter;
});
