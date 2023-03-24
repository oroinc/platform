import Row from 'orodatagrid/js/datagrid/row';

const SortRowsDragNDropRow = Row.extend({
    /**
     * @property {string}
     */
    selectedClass: 'selected',

    /**
     * @property {string}
     */
    overturnedClass: 'overturned',

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
            'change:_selected model': 'toggleSelectedClass',
            'change:_overturned model': 'toggleOverturnedClass'
        };
    },

    /**
     * @inheritdoc
     */
    render() {
        this.$el.data('modelId', this.model.id);
        this.toggleOrderClass();
        this.toggleSelectedClass();
        if (this.model.isSeparator()) {
            // turn off standard cells rendering for a separator row
            this.renderItems = false;
            // render single spacer cell
            const cell = document.createElement('td');
            cell.setAttribute('aria-hidden', 'true');
            cell.setAttribute('colspan', this.columns.length);
            this.$el.html(cell);
            this.$el.addClass('draggable-separator');
        }
        return SortRowsDragNDropRow.__super__.render.call(this);
    },

    /**
     * Adds or removes specific class to highlight that the row has a sort order
     */
    toggleOrderClass() {
        const order = this.model.get('_sortOrder');
        this.$el.toggleClass('row-has-sort-order', order !== void 0);
    },

    /**
     * Adds or removes specific class to highlight that the row is selected
     */
    toggleSelectedClass() {
        const selected = this.model.get('_selected') ?? false;
        this.$el.toggleClass(this.selectedClass, selected);
    },

    /**
     * Adds or removes specific class to highlight that the row has been overturned by separator
     */
    toggleOverturnedClass() {
        const overturned = this.model.get('_overturned') ?? false;
        this.$el.toggleClass(this.overturnedClass, overturned);
    }
});

export default SortRowsDragNDropRow;
