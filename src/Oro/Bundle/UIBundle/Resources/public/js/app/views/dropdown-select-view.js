define(function(require) {
    'use strict';

    var DropdownSelectView;
    var $ = require('jquery');
    var _ = require('underscore');
    var dropdownSelectTemplate = require('tpl!oroui/templates/dropdown-select.html');
    var BaseView = require('oroui/js/app/views/base/view');

    DropdownSelectView = BaseView.extend({
        template: dropdownSelectTemplate,
        className: 'operator',
        selectOptions: [],
        selectedValue: null,
        buttonClass: 'btn',
        useButtonGroup: true,
        useCaret: true,
        /**
         * Label of dropdown control
         * @type {string}
         */
        label: '',

        optionNames: BaseView.prototype.optionNames.concat([
            'selectOptions', 'selectedValue', 'buttonClass', 'useButtonGroup', 'useCaret', 'label'
        ]),

        /**
         * @inheritDoc
         */
        constructor: function DropdownSelectView() {
            DropdownSelectView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            if (!_.has(options, 'selectedValue')) {
                var firstOption = _.first(this.selectOptions);
                if (_.isString(firstOption)) {
                    this.selectedValue = firstOption;
                } else if (_.isObject(firstOption) && _.has(firstOption, 'value')) {
                    this.selectedValue = firstOption.value;
                } else {
                    this.selectedValue = _.first(_.values(firstOption));
                }
            }
            DropdownSelectView.__super__.initialize.call(this, options);
        },

        events: {
            'click .dropdown-menu a': '_onSelect'
        },

        render: function() {
            DropdownSelectView.__super__.render.call(this);
            this.$el.attr('data-value', this.selectedValue).data('value', this.selectedValue);
            return this;
        },

        getTemplateData: function() {
            var data = DropdownSelectView.__super__.getTemplateData.call(this);
            _.extend(data, _.pick(this, 'buttonClass', 'useButtonGroup', 'useCaret', 'label'));
            data.options = _.map(this.selectOptions, this._selectOptionIteratee, this);
            var selectedOption = _.findWhere(data.options, {selected: true});
            data.selectedLabel = selectedOption.label;
            data.selectedValue = selectedOption.value;
            return data;
        },

        _selectOptionIteratee: function(option) {
            var value;
            var label;
            if (_.isString(option)) {
                value = option;
            } else if (_.isArray(option)) {
                value = option[0];
                label = option[1];
            } else if (_.isObject(option)) {
                value = option.value;
                label = option.label;
            }

            var result = {
                value: value || label,
                label: label || value
            };

            if (this.selectedValue && value === this.selectedValue) {
                result.selected = true;
            }

            return result;
        },

        _onSelect: function(e) {
            e.preventDefault();
            this.select($(e.target).data('value'));
        },

        select: function(value) {
            this.selectedValue = value;
            var escapedValue = value.replace(/[&<>"'`]/g, function(a) {
                return '\\' + a;
            });
            var $option = this.$('[data-value="' + escapedValue + '"]');
            this.$('.dropdown-menu li').removeClass('selected');
            this.$('.dropdown-menu li [data-value]').removeAttr('aria-selected');
            $option.closest('li').addClass('selected');
            $option.attr('aria-selected', 'true');
            this.$('.current-label').text($option.text());
            this.trigger('change', this.selectedValue);
        },

        getValue: function() {
            return this.selectedValue;
        },

        setValue: function(value) {
            this.select(value);
        }
    });

    return DropdownSelectView;
});
