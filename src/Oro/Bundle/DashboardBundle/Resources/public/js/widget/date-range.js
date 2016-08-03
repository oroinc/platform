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

        /**
         * @inheritDoc
         */
        events: {
            'change .date-visual-element': '_onClickUpdateCriteria',
            'change select[name=date_part], input[name$="[type]"]': 'onChangeFilterType',
            'change select[name=""]': 'onChangeFilterTypeView'
        },

        /**
         * @inheritDoc
         */
        autoUpdateRangeFilterType: false,

        initialize: function(options) {
            WidgetConfigDateRangeFilter.__super__.initialize.apply(this, arguments);
            options.$form.on('submit' + this.eventNamespace(), _.bind(this.onSubmit, this));
        },

        onSubmit: function(e) {
            var value = _.extend({}, this.emptyValue, this.getValue());
            if (_.values(this.typeValues).indexOf(parseInt(value.type)) !== -1 &&
                !value.value.start && !value.value.end
            ) {
                this.$('select[name=""]').eq(0).val(this.typeDefinedValues.all_time).change();
                this.applyValue();
            }
        },

        onChangeFilterTypeView: function(e) {
            var val = parseInt($(e.target).val());
            if (val === this.customChoice.value) {
                val = this.$('select[name=""]').eq(1).val();
            }
            this.$(this.criteriaValueSelectors.date_type).val(val).change();
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

            this.options.$form.off(this.eventNamespace());
            WidgetConfigDateRangeFilter.__super__.dispose.apply(this, arguments);
        },

        changeFilterType: function(value) {
            WidgetConfigDateRangeFilter.__super__.changeFilterType.apply(this, arguments);

            var type = parseInt(value, 10);
            if (!isNaN(type)) {
                var $select = this.$('.selector:has(select[name=""]):eq(1), select[name=""]:eq(1)').eq(0);
                if (_.values(this.typeDefinedValues).indexOf(type) > -1) {
                    $select.hide();
                } else {
                    // set correct width of uniform widget in case <select> was hidden before, since widget
                    // wasn't initialized yet
                    var $hiddenSelect = $('select[name=""]:eq(1)');
                    if ($hiddenSelect.css('display') === 'none' &&
                        $hiddenSelect.data('bound-input-widget') === 'uniform'
                    ) {
                        $hiddenSelect.css('display', '').data('input-widget').refresh();
                    }

                    $select.css('display', '');
                }
            }
        },

        _getParts: function() {
            var parts = WidgetConfigDateRangeFilter.__super__._getParts.apply(this, arguments);
            if (!this.valueTypes) {
                return parts;
            }

            parts.pop();
            var value = _.extend({}, this.emptyValue, this.getValue());
            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var datePartTemplate = this._getTemplate(this.fieldTemplateSelector);

            var typeDefinedValues = _.values(this.typeDefinedValues);
            var typeDefinedValueChoices = _.filter(this.choices, function(choice) {
                return typeDefinedValues.indexOf(choice.data) !== -1;
            });
            typeDefinedValueChoices.push(this.customChoice);
            parts.push(
                datePartTemplate({
                    name: '',
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
    });

    return WidgetConfigDateRangeFilter;
});
