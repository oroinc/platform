import StringCell from 'oro/datagrid/cell/string-cell';

const HtmlTemplateCell = StringCell.extend({
    constructor: function HtmlTemplateCell(options) {
        HtmlTemplateCell.__super__.constructor.call(this, options);
    },

    getTemplateData: function() {
        return {
            ...this.model.toJSON(),
            _cid: this.cid,
            _metadata: {
                ...this.column.get('metadata')
            }
        };
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
