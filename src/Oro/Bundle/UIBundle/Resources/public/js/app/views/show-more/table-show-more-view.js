import _ from 'underscore';
import AbstractShowMoreView from 'oroui/js/app/views/show-more/abstract-show-more-view';

const TableShowMoreView = AbstractShowMoreView.extend({
    alwaysVisibleItems: 3,

    itemSelector: 'tbody tr',

    /**
     * @inheritdoc
     */
    constructor: function TableShowMoreView(options) {
        Object.assign(this, _.pick(options, 'alwaysVisibleItems'));

        TableShowMoreView.__super__.constructor.call(this, options);
    },

    render() {
        TableShowMoreView.__super__.render.call(this);

        this.$(_.rest(this.items, this.alwaysVisibleItems)).addClass('item-to-hide');
    },

    numItemsToHide() {
        return this.items.length > this.alwaysVisibleItems ? this.items.length - this.alwaysVisibleItems : 0;
    }
});

export default TableShowMoreView;
