define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './text-filter'
], function($, _, __, tools, TextFilter) {
    'use strict';

    var ChoiceFilter;

    /**
     * Choice filter: filter type as option + filter value as string
     *
     * @export  oro/filter/choice-filter
     * @class   oro.filter.ChoiceFilter
     * @extends oro.filter.TextFilter
     */
    ChoiceFilter = TextFilter.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#choice-filter-template',

        /**
         * Selectors for filter criteria elements
         *
         * @property {Object}
         */
        criteriaValueSelectors: {
            value: 'input[name="value"]',
            type: 'input[type="hidden"]:last'
        },

        /**
         * @property {boolean}
         */
        wrapHintValue: true,

        /**
         * Filter events
         *
         * @property
         */
        events: {
            'keyup input': '_onReadCriteriaInputKey',
            'keydown [type="text"]': '_preventEnterProcessing',
            'click .filter-update': '_onClickUpdateCriteria',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'click .choice-value': '_onClickChoiceValue'
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var opts = _.pick(options || {}, 'choices');
            _.extend(this, opts);

            // init filter content options if it was not initialized so far
            if (_.isUndefined(this.choices)) {
                this.choices = [];
            }
            // temp code to keep backward compatible
            if ($.isPlainObject(this.choices)) {
                this.choices = _.map(this.choices, function(option, i) {
                    return {value: i.toString(), label: option};
                });
            }

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value),
                    value: ''
                };
            }

            ChoiceFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.choices;
            delete this.emptyValue;
            ChoiceFilter.__super__.dispose.call(this);
        },

        render: function() {
            // render only wrapper (a button and a dropdown container e.g.)
            this._wrap('');
            // if there's no any wrapper, means it's embedded filter
            if (this.$el.html() === '') {
                this._renderCriteria();
            }
            return this;
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            var value = _.extend({}, this.emptyValue, this.value);
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this.choices)) {
                var foundChoice = _.find(this.choices, function(choice) {
                    return (choice.value === value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }
            var $filter = $(this.template({
                name: this.name,
                choices: this.choices,
                selectedChoice: value.type,
                selectedChoiceLabel: selectedChoiceLabel,
                value: value.value
            }));
            this._appendFilter($filter);
            this._updateDOMValue();
            this._criteriaRenderd = true;
        },

        _showCriteria: function() {
            if (!this._criteriaRenderd) {
                this._renderCriteria();
            }
            ChoiceFilter.__super__._showCriteria.apply(this, arguments);
        },

        _onClickCriteriaSelector: function() {
            ChoiceFilter.__super__._onClickCriteriaSelector.apply(this, arguments);
            this._updateValueField();
        },

        _onClickChoiceValue: function() {
            ChoiceFilter.__super__._onClickChoiceValue.apply(this, arguments);
            this._updateValueField();
        },

        reset: function() {
            ChoiceFilter.__super__.reset.apply(this, arguments);
            this._updateValueField();
        },

        _updateValueField: function() {
            var type;
            var isEmptyType;
            var valueFrame = this.$('.value-field-frame');
            if (!valueFrame.length) {
                return;
            }
            // update left and right margins of value field frame
            var leftWidth = this.$('.choice-filter .dropdown-toggle').outerWidth();
            var rightWidth = this.$('.filter-update').outerWidth();
            valueFrame.css('margin-left', leftWidth);
            valueFrame.css('padding-right', rightWidth);
            // update class of criteria dropdown
            type = this.$(this.criteriaValueSelectors.type).val();
            isEmptyType = this.isEmptyType(type);
            this.$('.filter-criteria').toggleClass('empty-type', isEmptyType);
            if (!isEmptyType) {
                this.$(this.criteriaValueSelectors.value).focus();
            }
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var option = null;

            if (!_.isUndefined(value.type)) {
                var type = value.type;
                option = this._getChoiceOption(type);

                if (this.isEmptyType(type)) {
                    return option ? option.label : this.placeholder;
                }
            }

            if (!value.value) {
                return this.placeholder;
            }

            var hintValue = this.wrapHintValue ? ('"' + value.value + '"') : value.value;

            return (option ? option.label + ' ' : '') + hintValue;
        },

        /**
         * Fetches option object for corresponded value type
         *
         * @param {*|string} valueType
         * @returns {{value: string, label: string}}
         * @private
         */
        _getChoiceOption: function(valueType) {
            return _.findWhere(this.choices, {value: valueType.toString()});
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.value, value.value);
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
            return this;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.criteriaValueSelectors.value),
                type: this._getInputValue(this.criteriaValueSelectors.type)
            };
        },

        /**
         * @inheritDoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (!tools.isEqualsLoosely(newValue, oldValue)) {
                this.trigger('update');
            }
        },

        /**
         * @inheritDoc
         */
        _onValueUpdated: function(newValue, oldValue) {
            // synchronize choice selector with new value
            var menu = this.$('.choice-filter .dropdown-menu');
            menu.find('li a').each(function() {
                var item = $(this);
                if (item.data('value').toString() === oldValue.type && item.parent().hasClass('active')) {
                    item.parent().removeClass('active');
                } else if (item.data('value').toString() === newValue.type && !item.parent().hasClass('active')) {
                    item.parent().addClass('active');
                    menu.parent().find('button').html(item.html() + '<span class="caret"></span>');
                }
            });

            ChoiceFilter.__super__._onValueUpdated.apply(this, arguments);
        }
    });

    return ChoiceFilter;
});
