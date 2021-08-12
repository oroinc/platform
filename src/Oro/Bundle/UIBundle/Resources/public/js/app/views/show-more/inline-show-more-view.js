import _ from 'underscore';
import AbstractShowMoreView from 'oroui/js/app/views/show-more/abstract-show-more-view';

const InlineShowMoreView = AbstractShowMoreView.extend({
    itemsContainerSelector: '[data-role="items-container"]',

    itemSelector: '> *',

    listen: {
        'layout:reposition mediator': 'render'
    },

    /**
     * @inheritdoc
     */
    constructor: function InlineShowMoreView(options) {
        InlineShowMoreView.__super__.constructor.call(this, options);
    },

    numItemsToHide() {
        if (this.items.length < 2) {
            return 0;
        }

        const threshold = this.items[0].getBoundingClientRect().bottom;
        const firstIndexToHide = _.findIndex(_.rest(this.items),
            item => item.getBoundingClientRect().top > threshold);

        return firstIndexToHide !== -1 ? this.items.length - firstIndexToHide - 1 : 0;
    }
});

export default InlineShowMoreView;
