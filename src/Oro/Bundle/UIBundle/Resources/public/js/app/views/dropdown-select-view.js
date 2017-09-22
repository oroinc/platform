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

        initialize: function(options) {
            _.extend(this,
                _.pick(options, 'selectOptions', 'selectedValue', 'buttonClass', 'useButtonGroup', 'useCaret'));
            if (!_.has(options, 'selectedValue')) {
                var firstOption = _.first(this.selectOptions);
                if (_.isString(firstOption)) {
                    this.selectedValue = firstOption;
                } else if (_.isObject(firstOption) && _.has(firstOption, 'value')){
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
            _.defaults(data, _.pick(this, 'buttonClass', 'useButtonGroup', 'useCaret'));
            data.options = _.map(this.selectOptions, this._selectOptionIteratee, this);
            var selectedOption = _.findWhere(data.options, {'selected': true});
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
            var $target = $(e.target);
            this.selectedValue = $target.data('value');
            this.$('.dropdown-menu li').removeClass('selected');
            $target.closest('li').addClass('selected');
            this.$('.current-label').text($target.text());
            this.$el.attr('data-value', this.selectedValue).data('value', this.selectedValue).trigger({
                type: 'change',
                value: this.selectedValue,
            });
        }
    });

    return DropdownSelectView;
});
