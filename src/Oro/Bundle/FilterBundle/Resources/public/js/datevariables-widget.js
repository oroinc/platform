define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const layout = require('oroui/js/layout');
    require('jquery-ui/widget');

    /**
     * Condition builder widget
     */
    $.widget('orofilter.dateVariables', {
        options: {
            onSelect: $.noop,
            value: null,
            part: 'value',
            dateParts: null,
            dateVars: null,
            tooltipTemplate: '<i class="fa-info-circle fa--offset-l fa--x-large" data-content="<%- content %>"' +
                ' data-placement="top" data-toggle="popover" data-original-title="<%- title %>"></i>',
            htmlTemplate: '<div class="ui-datevariables-div <%- attributes %>">' +
                '<span class="datevariables-title"><%- title %></span> <%= tooltipHTML %><ul>' +
                '<% _.each(dateVars, function(dateVariable, varCode) { %>' +
                '<li><a class="ui_date_variable" href="#" data-code="<%- varCode %>"><%- dateVariable %></a></li>' +
                '<% }); %>' +
                '</ul></div>'
        },

        _create: function() {
            this.render();
            this._on({
                'click .ui-datevariables-div a.ui_date_variable': 'onSelectVar'
            });
        },

        _destroy: function() {
            this.element.empty();
        },

        setPart: function(part) {
            this.options.part = part;

            // re-render on change part
            this.render();
        },

        getPart: function() {
            return this.options.part;
        },

        onSelectVar: function(e) {
            const variable = e.target.text;
            this.options.onSelect(variable);
            e.preventDefault();
        },

        render: function() {
            const o = this.options;
            const currentDatePart = o.part;
            const dateVars = this._getVariablesByPart(currentDatePart);
            const tooltipTemplate = _.template(o.tooltipTemplate);
            const htmlTemplate = _.template(o.htmlTemplate);

            const $dv = $(htmlTemplate({
                attributes: '',
                title: __('oro.filter.date.variable.title'),
                tooltipHTML: tooltipTemplate({
                    content: __('oro.filter.date.variable.tooltip'),
                    title: __('oro.filter.date.variable.tooltip_title')
                }),
                dateVars: dateVars
            }));

            this.element.html($dv);
            layout.initPopover(this.element);
        },

        _getVariablesByPart: function(datePart) {
            const dateVars = this.options.dateVars;
            return dateVars[datePart] ? dateVars[datePart] : dateVars.value;
        }
    });

    return $;
});
