import HtmlCell from 'oro/datagrid/cell/html-cell';

const SortIconCell = HtmlCell.extend({
    /**
     * @inheritdoc
     */
    className: 'sort-icon-cell',

    /**
     * @inheritdoc
     */
    constructor: function SortIconCell(options) {
        SortIconCell.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        SortIconCell.__super__.initialize.call(this, options);
        this.listenTo(this.model, `change:${this.model.sortOrderAttrName}`, this.render);
    },

    /**
     * @inheritdoc
     */
    render: function() {
        const icon = document.createElement('span');
        icon.setAttribute('aria-hidden', 'true');
        icon.classList.add('sort-icon');
        this.$el.html(icon);

        return this;
    }
});

export default SortIconCell;
