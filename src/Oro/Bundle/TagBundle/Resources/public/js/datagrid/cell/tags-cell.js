define(function(require) {
    'use strict';

    var TagsCell;
    var Backgrid = require('backgrid');
    var _ = require('underscore');
    var routing = require('routing');
    var TagsView = require('orotag/js/app/views/viewer/tags-view');

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
    TagsCell = Backgrid.StringCell.extend(_.extend(
        _.pick(TagsView.prototype, [
            'template',
            'getTemplateFunction',
            'getTemplateData',
            'render'
        ]), {

            showDefault: false,
            /**
             * @property {string}
             */
            type: 'tags',

            /**
             * @property {string}
             */
            className: 'tags-cell tags-container',

            initialize: function() {
                Backgrid.StringCell.__super__.initialize.apply(this, arguments);
                this.fieldName = this.column.get('name');
                //TODO move url generation to server side
                var tags = this.model.get(this.fieldName);
                tags = _.map(tags, function(tag) {
                    if (!tag.hasOwnProperty('url')) {
                        tag.url = routing.generate('oro_tag_search', {
                            id: tag.id
                        });
                    }
                    return tag;
                });
                this.model.set(this.fieldName, tags);
            }
        })
    );

    return TagsCell;
});
