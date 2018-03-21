define(function(require) {
    'use strict';

    var WidgetConfigDateRangeFilter;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var DateFilter = require('oro/filter/date-filter');
    var tools = require('oroui/js/tools');

    WidgetConfigDateRangeFilter = DateFilter.extend({
        customChoice: {
            attr: [],
            data: -5,
            label: 'Custom',
            value: -5
        },

        fieldsDataName: {
            datePart: 'date_part',
            customPart: 'custom_part'
        },

        domCache: null,

        /**
         * @inheritDoc
         */
        events: {
            'change select': 'skipOnChangeFilterTypeHandler',
            'change .date-visual-element': '_onClickUpdateCriteria',
            'change select[name=date_part], input[name$="[type]"]': 'onChangeFilterType',
            'change select[data-name$="_part"]': 'onChangeFilterTypeView'
        },

        /**
         * @inheritDoc
         */
        autoUpdateRangeFilterType: false,

        /**
         * @inheritDoc
         */
        constructor: function WidgetConfigDateRangeFilter() {
            WidgetConfigDateRangeFilter.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WidgetConfigDateRangeFilter.__super__.initialize.apply(this, arguments);
            options.$form.on('submit' + this.eventNamespace(), _.bind(this.onSubmit, this));
        },

        createDomCache: function() {
            this.domCache = {
                $datePart: this.$('select[data-name="' + this.fieldsDataName.datePart + '"]'),
                $customPart: this.$('select[data-name="' + this.fieldsDataName.customPart + '"]'),
                $dateTypeCriteriaValue: this.$(this.criteriaValueSelectors.date_type)
            };
        },

        onSubmit: function() {
            var value = _.extend({}, this.emptyValue, this.getValue());
            if (_.values(this.typeValues).indexOf(parseInt(value.type)) !== -1 &&
                !value.value.start && !value.value.end
            ) {
                var defaultTypeValue = this.getDefaultTypeValue();
                this.domCache.$datePart.val(defaultTypeValue).change();
            }
        },

        onChangeFilterTypeView: function(e) {
            var val = parseInt($(e.target).val());
            if (val === this.customChoice.value) {
                val = this.domCache.$customPart.val();
            }
            this.domCache.$dateTypeCriteriaValue.val(val).change();
            this.applyValue();
        },

        /**
         * Render filter view
         * Update value after render
         *
         * @return {*}
         */
        render: function() {
            WidgetConfigDateRangeFilter.__super__.render.call(this);
            this.setValue(this.value);
            return this;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.domCache = null;
            this.options.$form.off(this.eventNamespace());
            WidgetConfigDateRangeFilter.__super__.dispose.apply(this, arguments);
        },

        changeFilterType: function(value) {
            WidgetConfigDateRangeFilter.__super__.changeFilterType.apply(this, arguments);

            var type = parseInt(value, 10);
            if (!isNaN(type)) {
                if (_.values(this.typeDefinedValues).indexOf(type) === -1) {
                    this.domCache.$customPart.show();
                } else {
                    this.domCache.$customPart.hide();
                }

                // set correct width of uniform widget
                if (this.domCache.$customPart.data('bound-input-widget') === 'uniform') {
                    this.domCache.$customPart.data('input-widget').refresh();
                }
            }
        },

        getDefaultTypeValue: function() {
            var choiceData = _.pluck(this.choices, 'data');
            return choiceData.indexOf(this.typeDefinedValues.all_time) === -1
                ? this.emptyValue.type
                : this.typeDefinedValues.all_time;
        },

        _getParts: function() {
            if (!this.valueTypes) {
                return WidgetConfigDateRangeFilter.__super__._getParts.apply(this, arguments);
            }

            var parts = [];
            var value = _.extend({}, this.emptyValue, this.getValue());
            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var datePartTemplate = this._getTemplate('fieldTemplate');

            var typeDefinedValues = _.values(this.typeDefinedValues);
            var typeDefinedValueChoices = _.filter(this.choices, function(choice) {
                return typeDefinedValues.indexOf(choice.data) !== -1;
            });
            typeDefinedValueChoices.push(this.customChoice);
            parts.push(
                datePartTemplate({
                    name: '',
                    dataName: this.fieldsDataName.datePart,
                    choices: typeDefinedValueChoices,
                    selectedChoice: typeDefinedValues.indexOf(value.type) !== -1 ? value.type : this.customChoice.value,
                    selectedChoiceLabel: selectedChoiceLabel,
                    popoverContent: __('oro.filter.date.info')
                })
            );

            var typeValues = _.values(this.typeValues);
            var typeValueChoices = _.filter(this.choices, function(choice) {
                return typeValues.indexOf(choice.data) !== -1;
            });
            parts.push(
                datePartTemplate({
                    name: '',
                    dataName: this.fieldsDataName.customPart,
                    choices: typeValueChoices,
                    selectedChoice: value.type,
                    selectedChoiceLabel: selectedChoiceLabel
                })
            );

            parts.push($('<input>').attr({
                type: 'hidden',
                name: this.name,
                value: value.type
            }).prop('outerHTML'));

            return parts;
        },

        _appendFilter: function($filter) {
            WidgetConfigDateRangeFilter.__super__._appendFilter.call(this, $filter);
            this.createDomCache();
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
         * Update value without triggering events
         *
         * @param value
         */
        updateValue: function(value) {
            this.value = tools.deepClone(value);
        },

        /**
         * @inheritDoc
         */
        _updateDOMValue: function() {
            return this._writeDOMValue(this._formatRawValue(this.getValue()));
        },

        /**
         * This method allow us to reset parent event observer
         */
        skipOnChangeFilterTypeHandler: function() {}
    });

    return WidgetConfigDateRangeFilter;
});
