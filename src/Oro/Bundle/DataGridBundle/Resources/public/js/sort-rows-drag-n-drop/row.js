import Row from 'orodatagrid/js/datagrid/row';

const SortRowsDragNDropRow = Row.extend({
    /**
     * @property {string}
     */
    selectedClass: 'selected',

    /**
     * @inheritdoc
     */
    constructor: function SortRowsDragNDropRow(options) {
        SortRowsDragNDropRow.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    listen() {
        return {
            [`change:${this.model.sortOrderAttrName} model`]: 'toggleOrderClass',
            'change:_selected model': 'toggleSelectedClass'
        };
    },

    /**
     * @inheritdoc
     */
    render() {
        this.$el.data('modelId', this.model.id);
        this.toggleOrderClass();
        this.toggleSelectedClass();
        return SortRowsDragNDropRow.__super__.render.call(this);
    },

    /**
     * Adds or removes specific class to show that the row has a sort order
     */
    toggleOrderClass() {
        const order = this.model.get('_sortOrder');

        this.$el.toggleClass('row-has-sort-order', order !== void 0);
    },

    /**
     * Adds or removes specific class to show that the row is selected
     */
    toggleSelectedClass() {
        const selected = this.model.get('_selected') ?? false;
        this.$el.toggleClass(this.selectedClass, selected);
    }
});

export default SortRowsDragNDropRow;
