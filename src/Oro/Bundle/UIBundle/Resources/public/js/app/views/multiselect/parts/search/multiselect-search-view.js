import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import MultiselectSearchModel from 'oroui/js/app/views/multiselect/parts/search/multiselect-search-model';
import template from 'tpl-loader!oroui/templates/multiselect/parts/search/multiselect-search-view.html';

export const cssConfig = {
    searchMain: 'multiselect__search',
    searchInput: 'input input--full input-with-search multiselect__search-input',
    searchResetBtn: 'btn btn--simple-colored clear-search-button',
    searchResetBtnHide: 'hidden',
    searchIcon: 'multiselect__search-icon'
};

const MultiselectSearchView = BaseMultiSelectView.extend({
    cssConfig,

    template,

    className() {
        return this.cssConfig.searchMain;
    },

    events: {
        'input [data-role="search-input"]': 'onSearchInput',
        'click [data-role="clear"]': 'clearSearchInput'
    },

    listen: {
        'reset collection': 'checkSearchVisibility',
        'add collection': 'checkSearchVisibility',
        'remove collection': 'checkSearchVisibility'
    },

    constructor: function MultiselectSearchView(...args) {
        MultiselectSearchView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.model = new MultiselectSearchModel({
            collection: options.collection,
            cssConfig: this.cssConfig,
            maxItemsForShowSearchBar: options.maxItemsForShowSearchBar
        });

        MultiselectSearchView.__super__.initialize.call(this, options);
    },

    render() {
        MultiselectSearchView.__super__.render.call(this);

        this.checkSearchVisibility();

        return this;
    },

    onSearchInput(event) {
        this.clearButtonVisibility(event.currentTarget.value === '');

        if (event.currentTarget.value === '') {
            return this.resetSearch();
        }

        const searchValue = event.currentTarget.value.toLowerCase();

        this.collection.each(model => {
            const isVisible = model.get('label').toLowerCase().includes(searchValue);
            model.set('hidden', !isVisible);
        });

        this.collection.visibilityChange();
    },

    resetSearch() {
        this.collection.each(model => model.set('hidden', false));

        this.collection.visibilityChange();
    },

    clearButtonVisibility(empty) {
        this.$('[data-role="clear"]').toggleClass(this.cssConfig.searchResetBtnHide, empty);
    },

    clearSearchInput() {
        this.$('[data-role="search-input"]').val('');

        this.resetSearch();
    },

    checkSearchVisibility() {
        this.$el.toggleClass('hidden', this.collection.length <= this.model.get('maxItemsForShowSearchBar'));
    }
});

export default MultiselectSearchView;
