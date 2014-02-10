/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'jquery-ui'], function ($, _) {
    'use strict';

    $.widget('oroui.dropdownSelect', {
        template: _.template(
            '<% if (useButtonGroup) { %><div class="btn-group"><% } %>' +
                '<a href="#" class="<%= buttonClass %> dropdown-toggle" ' +
                    'data-toggle="dropdown" data-value="<%= selected.value %>"' +
                    '><span><%= selected.label %></span><% if (useCaret) { %> <span class="caret"></span><% } %></a>' +
                '<ul class="dropdown-menu">' +
                    '<% _.each(options, function (option) { %>' +
                    '<li<% if (selected.value == option.value) { %> class="selected"<% } %>><a ' +
                        'href="#" data-value="<%= option.value %>"><%= option.label %></a></li>' +
                    '<% }); %>' +
                '</ul>' +
                '<% if (useButtonGroup) { %></div><% } %>'
        ),

        options: {
            options: [],
            selected: null,
            buttonClass: 'btn',
            useButtonGroup: true,
            useCaret: true
        },

        _create: function () {
            this._mapSelectOptions();
            this.element.append(this.template(this.options));
            this._on({
                'click .dropdown-menu a': this._onSelect
            });
        },

        _mapSelectOptions: function () {
            var selected = this.options.selected;
            this.options.options = $.map(this.options.options, function (option) {
                var value = null;
                if (_.isString(option)) {
                    value = option;
                } else if (_.isArray(option)) {
                    option = _.object(['value', 'label'], option);
                } else if (_.isObject(option) && !(_.has(option, 'value') && _.has(option, 'label'))) {
                    value = option.value || option.label;
                }
                option = value !== null ? {value: value, label: value} : option;
                if (!_.isEmpty(selected) && (selected === option.value || selected.value === option.value)) {
                    selected = option;
                }
                return option;
            });
            if (_.isEmpty(selected)) {
                selected = this.options.options[0];
            }
            this.options.selected = selected;
        },

        _onSelect: function (e) {
            e.preventDefault();
            this._select($(e.target).data('value'));
        },

        _select: function (value) {
            var option = _.findWhere(this.options.options, {value: value});
            if (option === this.options.selected) {
                return;
            }
            this.options.selected = option;
            this.element
                .find('.dropdown-menu li').removeClass('selected')
                .has('[data-value=' + value + ']').addClass('selected');
            this.element
                .find('[data-toggle=dropdown]').data('value', value)
                .find('span:first-child').text(option.label);
            this.element.trigger({
                type: 'change',
                value: value,
                option: _.clone(option)
            });
        }
    });

    return $;
});
