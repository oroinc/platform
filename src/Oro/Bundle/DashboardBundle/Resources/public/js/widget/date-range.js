define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const DateFilter = require('oro/filter/date-filter');
    const tools = require('oroui/js/tools');

    const WidgetConfigDateRangeFilter = DateFilter.extend({
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
         * @inheritdoc
         */
        events: {
            'change select': 'skipOnChangeFilterTypeHandler',
            'change .date-visual-element': '_onClickUpdateCriteria',
            'change select[name=date_part], input[name$="[type]"]': 'onChangeFilterType',
            'change select[data-name$="_part"]': 'onChangeFilterTypeView'
        },

        /**
         * @inheritdoc
         */
        autoUpdateRangeFilterType: false,

        /**
         * @inheritdoc
         */
        constructor: function WidgetConfigDateRangeFilter(options) {
            WidgetConfigDateRangeFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WidgetConfigDateRangeFilter.__super__.initialize.call(this, options);
            options.$form.on('submit' + this.eventNamespace(), this.onSubmit.bind(this));
        },

        createDomCache: function() {
            this.domCache = {
                $datePart: this.$('select[data-name="' + this.fieldsDataName.datePart + '"]'),
                $customPart: this.$('select[data-name="' + this.fieldsDataName.customPart + '"]'),
                $dateTypeCriteriaValue: this.$(this.criteriaValueSelectors.date_type)
            };
        },

        onSubmit: function() {
            const value = _.extend({}, this.emptyValue, this.getValue());
            if (_.values(this.typeValues).indexOf(parseInt(value.type)) !== -1 &&
                !value.value.start && !value.value.end
            ) {
                const defaultTypeValue = this.getDefaultTypeValue();
                this.domCache.$datePart.val(defaultTypeValue).change();
            }
        },

        onChangeFilterTypeView: function(e) {
            let val = parseInt($(e.target).val());
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
            WidgetConfigDateRangeFilter.__super__.dispose.call(this);
        },

        changeFilterType: function(value) {
            const oldRect = this.el.getBoundingClientRect();

            WidgetConfigDateRangeFilter.__super__.changeFilterType.call(this, value);

            const type = parseInt(value, 10);
            if (!isNaN(type)) {
                if (_.values(this.typeDefinedValues).indexOf(type) === -1) {
                    this.domCache.$customPart.show();
                } else {
                    this.domCache.$customPart.hide();
                }

                // set correct width of uniform widget
                if (this.domCache.$customPart.data('bound-input-widget') === 'uniform') {
                    this.domCache.$customPart.data('input-widget').refresh();

                    if (_.values(this.typeDefinedValues).indexOf(type) === -1) {
                        this.domCache.$customPart.data('input-widget').$container.show();
                    } else {
                        this.domCache.$customPart.data('input-widget').$container.hide();
                    }
                }

                const newRect = this.el.getBoundingClientRect();

                if (oldRect.width !== newRect.width || oldRect.height !== newRect.height) {
                    this.$el.trigger('content:changed');
                }
            }
        },

        getDefaultTypeValue: function() {
            const choiceData = _.pluck(this.choices, 'data');
            return choiceData.indexOf(this.typeDefinedValues.all_time) === -1
                ? this.emptyValue.type
                : this.typeDefinedValues.all_time;
        },

        _getParts: function() {
            if (!this.valueTypes) {
                return WidgetConfigDateRangeFilter.__super__._getParts.call(this);
            }

            const parts = [];
            const value = _.extend({}, this.emptyValue, this.getValue());
            const selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            const datePartTemplate = this._getTemplate('fieldTemplate');

            const typeDefinedValues = _.values(this.typeDefinedValues);
            const typeDefinedValueChoices = _.filter(this.choices, function(choice) {
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

            const typeValues = _.values(this.typeValues);
            const typeValueChoices = _.filter(this.choices, function(choice) {
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
         * @inheritdoc
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
         * @inheritdoc
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
