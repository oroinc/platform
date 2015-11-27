define([
    'backgrid',
    'underscore',
    'orotranslation/js/translator'
], function(Backgrid, _, __) {
    'use strict';

    var TagsCell;

    /**
     * Cell able to display tags values.
     *
     * Requires income data format:
     * ```javascript
     * var cellValue = [{id: 1, text: 'tag-1', locked: false}, {id: 2, text: 'tag-2', locked: true}, ...];
     * ```
     *
     * Also please prepare and pass choices through cell configuration
     *
     * @export  oro/datagrid/cell/tags-cell
     * @class   oro.datagrid.cell.TagsCell
     * @extends oro.datagrid.cell.StringCell
     */
    TagsCell = Backgrid.StringCell.extend({
        /**
         * @property {string}
         */
        type: 'tags',

        /**
         * @property {string}
         */
        className: 'tags-cell',

        /**
         * @inheritDoc
         */
        render: function() {
            var value = this.model.get(this.column.get('name'));
            var html = '';

            if (value === null || value === void 0) {
                // assume empty
                value = [];
            }

            if (_.isArray(value) && value.length) {
                html = value.map(function(v) {
                    return '<span class="tags-item">' + v.text + '</span> ';
                });
            } else {
                html = 'N/A';
            }

            this.$el.html(html);

            return this;
        }
    });

    return TagsCell;
});
