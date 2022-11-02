import StringCell from 'oro/datagrid/cell/string-cell';
import __ from 'orotranslation/js/translator';

const HtmlTemplateCell = StringCell.extend({
    constructor: function HtmlTemplateCell(options) {
        HtmlTemplateCell.__super__.constructor.call(this, options);
    },

    _attributes() {
        return {
            'data-blank-content': null
        };
    },

    getTemplateData: function() {
        const data = {
            ...this.model.toJSON(),
            _cid: this.cid,
            _metadata: {
                ...this.column.get('metadata')
            },
            _attrs: this._collectAttributes()
        };
        const value = this.model.get(this.column.get('name'));

        if (
            value === void 0 ||
            value === null ||
            (typeof value === 'string' && value.trim().length === 0)
        ) {
            data['_blankContent'] = __('oro.datagrid.cell.blank.placeholder');
        }

        return data;
    },

    getTemplateFunction: function(templateKey = 'default') {
        if (typeof this.column.get('metadata').template === 'function') {
            return this.column.get('metadata').template;
        }
        return this.column.get('metadata').template[templateKey];
    },

    render: function() {
        const template = this.getTemplateFunction();
        this.$el.html(template(this.getTemplateData()));
        return this;
    }
});

export default HtmlTemplateCell;
