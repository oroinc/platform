define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const layout = require('oroui/js/layout');
    const tooltipTemplate = require('tpl-loader!orofilter/templates/datevariables-tooltip.html');
    const htmlTemplate = require('tpl-loader!orofilter/templates/datevariables-html.html');
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
            tooltipTemplate: tooltipTemplate,
            htmlTemplate: htmlTemplate
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
            const tooltipTemplate = o.tooltipTemplate;
            const htmlTemplate = o.htmlTemplate;

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
