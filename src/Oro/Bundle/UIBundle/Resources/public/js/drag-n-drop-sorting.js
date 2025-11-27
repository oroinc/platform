import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import 'jquery-ui/widgets/sortable';

const DraggableSortingView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function DraggableSortingView(options) {
        DraggableSortingView.__super__.constructor.call(this, options);
    },

    render: function() {
        this.initSortable();
        this.reindexValues();
        return this;
    },

    reindexValues: function() {
        let index = 1;
        this.$('[name$="[_position]"]').each(function() {
            $(this).val(index++);
        });
    },

    initSortable: function() {
        this.$('.sortable-wrapper').sortable({
            tolerance: 'pointer',
            delay: 100,
            containment: 'parent',
            handle: '[data-name="sortable-handle"]',
            stop: this.reindexValues.bind(this)
        });
    }
});

export default DraggableSortingView;
