/*global define, require*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/layout', 'jquery-ui'],
    function ($, _, __, layout) {
    'use strict';

    /**
     * Condition builder widget
     */
    $.widget('orofilter.dateVariables', {
        options: {
            $input: null,
            isRTL: false,
            value: null,
            part:  'value',
            dateParts: null,
            dateVars: null,
            tooltipTemplate: '<i class="icon-info-sign" data-content="<%- content %>"' +
                ' data-placement="top" data-toggle="popover" data-original-title="<%- title %>"></i>',
            htmlTemplate: '<div class="ui-datevariables-div <%- attributes %>">' +
                '<b><%- title %></b><%= tooltipHTML %><ul>' +
                '<% _.each(dateVars, function(dateVariable, varCode) { %>' +
                '<li><a class="ui_dvariable" href="#" data-code="<%- varCode %>"><%- dateVariable %></a></li>' +
                '<% }); %>' +
                '</ul></div>'
        },

        _create: function () {
            this.render();
        },

        setPart: function (part) {
            this.options.part = part;

            // re-render on change part
            this.render();
        },

        onSelectVar: function (e) {
            var variable = e.target.text;

            //dvInst.inst.settings.timepicker.timeDefined = false;

            this.options.$input.val(variable);
            this.options.$input.trigger("change");

            e.preventDefault();
        },

        render: function () {
            var o               = this.options,
                currentDatePart = o.part,
                dateVars        = this._getVariablesByPart(currentDatePart),
                tooltipTemplate = _.template(o.tooltipTemplate),
                htmlTemplate    = _.template(o.htmlTemplate);

            var $dv = $(htmlTemplate({
                attributes:  o.isRTL ? ' ui-datevariables-rtl' : '',
                title:       __('oro.filter.date.variable.title'),
                tooltipHTML: tooltipTemplate({
                    content: __('oro.filter.date.variable.tooltip'),
                    title:   __('oro.filter.date.variable.tooltip_title')
                }),
                dateVars:    dateVars
            }));

            var widget = this.widget();
            widget.empty().append($dv);

            layout.initPopover(widget);

            widget.find('.ui-datevariables-div a.ui_dvariable').click(
                _.bind(this.onSelectVar, this)
            );
        },

        _getVariablesByPart: function (datePart) {
            var dateVars = this.options.dateVars;
            return dateVars[datePart] ? dateVars[datePart] : dateVars['value'];
        }
    });

    return $;
});
