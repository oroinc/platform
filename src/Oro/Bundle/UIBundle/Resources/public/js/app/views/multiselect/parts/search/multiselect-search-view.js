import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import MultiselectSearchModel from 'oroui/js/app/views/multiselect/parts/search/multiselect-search-model';
import template from 'tpl-loader!oroui/templates/multiselect/parts/search/multiselect-search-view.html';

export const cssConfig = {
    searchMain: 'multiselect__search',
    searchInput: 'input input--full input-with-search multiselect__search-input',
    searchResetBtn: 'btn btn--simple-colored clear-search-button',
    searchResetBtnHide: 'hide',
    searchHide: 'hide',
    searchIcon: 'multiselect__search-icon'
};

const MultiselectSearchView = BaseMultiSelectView.extend({
    Model: MultiselectSearchModel,

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

        this.collection.getAllItems().forEach(model => {
            const isVisible = model.get('label').toLowerCase().includes(searchValue);
            model.setHidden(!isVisible);
        });

        this.collection.visibilityChange();
    },

    resetSearch() {
        this.collection.each(model => model.setHidden(false));

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
        this.$el.toggleClass(this.cssConfig.searchHide,
            this.collection.getAllItemsCount() < this.model.get('maxItemsForShowSearchBar')
        );
    }
});

export default MultiselectSearchView;
