define([
    'backgrid',
    'underscore',
    'orotranslation/js/translator',
    'orotag/js/app/views/viewer/tags-view',
    'routing'
], function(Backgrid, _, __, TagsView, routing) {
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
        className: 'tags-cell tags-container',

        template: TagsView.prototype.template,

        tagSortCallback: TagsView.prototype.tagSortCallback,

        /**
         * @inheritDoc
         */
        render: function() {
            // preparing urls
            var data = this.model.toJSON();
            var tags = data[this.column.get('name')];
            for (var i = 0; i < tags.length; i++) {
                tags[i].url = routing.generate('oro_tag_search', {
                    id: tags[i].id
                });
            }

            this.$el.html(this.template({
                model: data,
                showDefault: false,
                fieldName: this.column.get('name'),
                tagSortCallback: this.tagSortCallback
            }));

            return this;
        }
    });

    return TagsCell;
});
