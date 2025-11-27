import Backgrid from 'backgrid';
import _ from 'underscore';
import routing from 'routing';
import TagsView from 'orotag/js/app/views/viewer/tags-view';
import template from 'tpl-loader!orotag/templates/viewer/tags-view.html';

/**
 * Cell able to display tags values.
 *
 * Requires income data format:
 * ```javascript
 * const cellValue = [{id: 1, text: 'tag-1'}, {id: 2, text: 'tag-2'}, ...];
 * ```
 *
 * Also please prepare and pass choices through cell configuration
 *
 * @export  oro/datagrid/cell/tags-cell
 * @class   oro.datagrid.cell.TagsCell
 * @extends oro.datagrid.cell.StringCell
 */
const TagsCell = Backgrid.StringCell.extend({
    template,

    getTemplateFunction: TagsView.prototype.getTemplateFunction,

    getTemplateData: TagsView.prototype.getTemplateData,

    render: TagsView.prototype.render,

    /**
     * @property {string}
     */
    type: 'tags',

    /**
     * @property {string}
     */
    className: 'tags-cell tags-container',

    initialize: function(options) {
        Backgrid.StringCell.__super__.initialize.call(this, options);
        this.fieldName = this.column.get('name');
        // Needs to move url generation to server side
        let tags = this.model.get(this.fieldName);
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
});

export default TagsCell;
