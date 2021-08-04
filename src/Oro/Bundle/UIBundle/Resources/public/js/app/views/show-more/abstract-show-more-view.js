import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';

const TOGGLE_SELECTOR = '[data-role="show-more"]';
const SHOW_LESS_CLASS = 'show-less';

const AbstractShowMoreView = BaseView.extend({
    autoRender: true,

    itemsContainerSelector: null,

    itemSelector: null,

    showMoreTranslationKey: 'oro.ui.show_more_view.show_more',

    showLessTranslationKey: 'oro.ui.show_more_view.show_less',

    events: {
        [`click ${TOGGLE_SELECTOR}`]: 'onShowMoreClick'
    },

    /**
     * @inheritdoc
     */
    constructor: function AbstractShowMoreView(options) {
        if (this.itemSelector === null) {
            throw new Error('Prop `itemSelector` has to be defined in a descendant');
        }

        AbstractShowMoreView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        AbstractShowMoreView.__super__.initialize.call(this, options);

        this.$itemsContainer = this.itemsContainerSelector
            ? this.$el.find(this.itemsContainerSelector)
            : this.$el;
        this.items = this.$itemsContainer.find(this.itemSelector).toArray();
        this.$showMore = this.$el.find(TOGGLE_SELECTOR);
    },

    onShowMoreClick(e) {
        this.$itemsContainer.toggleClass(SHOW_LESS_CLASS);
        this.render();
    },

    render() {
        const numToHide = this.numItemsToHide();

        if (numToHide > 0) {
            const label = this.$itemsContainer.hasClass(SHOW_LESS_CLASS)
                ? __(this.showMoreTranslationKey, {number: numToHide}, numToHide)
                : __(this.showLessTranslationKey);

            this.$showMore.text(label).attr('title', label).show();
        } else {
            this.$itemsContainer.removeClass(SHOW_LESS_CLASS);
            this.$showMore.hide();
        }

        return this;
    },

    /**
     * Calculates how many items can be hidden
     *
     * @abstract
     * @return {number}
     */
    numItemsToHide() {
        throw new Error('Method `numItemsToHide` has to be implemented by a descendant');
    }
});

export default AbstractShowMoreView;
