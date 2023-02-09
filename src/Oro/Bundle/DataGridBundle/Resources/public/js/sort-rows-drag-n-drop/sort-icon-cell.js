import HtmlCell from 'oro/datagrid/cell/html-cell';

const SortIconCell = HtmlCell.extend({
    /**
     * @inheritdoc
     */
    optionNames: HtmlCell.prototype.optionNames.concat([
        'sortedIcon', 'unsortedIcon'
    ]),

    /**
     * @inheritdoc
     */
    className: 'sort-icon-cell',

    sortedIcon: 'fa-anchor',

    unsortedIcon: 'fa-arrows-v',

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
        const order = this.model.get('_sortOrder');

        icon.setAttribute('aria-hidden', 'true');
        icon.classList.add(
            'sort-icon',
            order === void 0 ? this.unsortedIcon : this.sortedIcon
        );
        this.$el.html(icon);

        return this;
    }
});

export default SortIconCell;
