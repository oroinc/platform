define(function(require) {
    'use strict';

    const Backgrid = require('backgrid');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');

    /**
     * Cell able to display multiselect values.
     *
     * Requires income data format:
     * ```javascript
     * const cellValue = [<id-1>, <id-2>, ...];
     * ```
     *
     * Also please prepare and pass choices through cell configuration
     *
     * @export  oro/datagrid/cell/multi-select-cell
     * @class   oro.datagrid.cell.MultiSelectCell
     * @extends oro.datagrid.cell.StringCell
     */
    const MultiSelectCell = Backgrid.StringCell.extend({
        /**
         * @property {string}
         */
        type: 'multiselect',

        /**
         * @property {string}
         */
        className: 'multi-select-cell',

        /**
         * @property {string}
         */
        ERROR_HTML: '<span style="color:red">' + __('Unexpected format') + '</span>',

        /**
         * @inheritdoc
         */
        constructor: function MultiSelectCell(options) {
            MultiSelectCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            let value = this.model.get(this.column.get('name'));
            const choices = this.choices;

            if (_.isString(value)) {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    this.$el.html(this.ERROR_HTML);
                    return this;
                }
            }

            if (value === null || value === void 0) {
                // assume empty
                value = [];
            }

            let html;
            try {
                html = value.length > 0 ? (
                    '<span class="multiselect-value-wrapper"><span class="value-item">' +
                    value
                        .map(function(item) {
                            return _.findKey(choices, function(value) {
                                return value === item;
                            });
                        })
                        .filter(function(item) {
                            return item;
                        })
                        .join('</span><span class="value-item">') +
                    '</span></span>'
                ) : '';
            } catch (e) {
                html = this.ERROR_HTML;
            }

            this.$el.html(html);

            return this;
        }
    });

    return MultiSelectCell;
});
