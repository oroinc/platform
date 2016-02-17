define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/layout',
    'jquery-ui'
], function($, _, __, layout) {
    'use strict';

    /**
     * Condition builder widget
     */
    $.widget('orofilter.dateVariables', {
        options: {
            onSelect: $.noop,
            value: null,
            part:  'value',
            dateParts: null,
            dateVars: null,
            tooltipTemplate: '<i class="icon-info-sign" data-content="<%- content %>"' +
                ' data-placement="top" data-toggle="popover" data-original-title="<%- title %>"></i>',
            htmlTemplate: '<div class="ui-datevariables-div <%- attributes %>">' +
                '<b><%- title %></b><%= tooltipHTML %><ul>' +
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
            var variable = e.target.text;
            this.options.onSelect(variable);
            e.preventDefault();
        },

        render: function() {
            var o = this.options;
            var currentDatePart = o.part;
            var dateVars = this._getVariablesByPart(currentDatePart);
            var tooltipTemplate = _.template(o.tooltipTemplate);
            var htmlTemplate = _.template(o.htmlTemplate);

            var $dv = $(htmlTemplate({
                attributes:  '',
                title:       __('oro.filter.date.variable.title'),
                tooltipHTML: tooltipTemplate({
                    content: __('oro.filter.date.variable.tooltip'),
                    title:   __('oro.filter.date.variable.tooltip_title')
                }),
                dateVars:    dateVars
            }));

            this.element.html($dv);
            layout.initPopover(this.element);
        },

        _getVariablesByPart: function(datePart) {
            var dateVars = this.options.dateVars;
            return dateVars[datePart] ? dateVars[datePart] : dateVars.value;
        }
    });

    return $;
});
