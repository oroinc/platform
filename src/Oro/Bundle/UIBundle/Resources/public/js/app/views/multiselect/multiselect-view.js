import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import MultiSelectCollectionView, {cssConfig as collectionCssConfig}
    from 'oroui/js/app/views/multiselect/collection/multiselect-collection-view';
import MultiselectViewModel from 'oroui/js/app/views/multiselect/models/multiselect-view-model';
import MultiSelectCollection from 'oroui/js/app/views/multiselect/collection/multiselect-collection';
import MultiselectHeaderView, {cssConfig as headerCssConfig}
    from 'oroui/js/app/views/multiselect/parts/header/multiselect-header-view';
import MultiselectFooterView, {cssConfig as footerCssConfig}
    from 'oroui/js/app/views/multiselect/parts/footer/multiselect-footer-view';
import MultiSelectItemModel from 'oroui/js/app/views/multiselect/collection/multiselect-item-model';
import MultiselectSearchView from 'oroui/js/app/views/multiselect/parts/search/multiselect-search-view';

export const cssConfig = {
    main: 'multiselect-view',
    sourceSelectHide: 'hidden',
    ...collectionCssConfig,
    ...headerCssConfig,
    ...footerCssConfig
};

/**
 * Multiselect view
 * It is used to render multiselect component
 *
 * @class MultiSelectView
 */
const MultiSelectView = BaseMultiSelectView.extend({
    optionNames: BaseMultiSelectView.prototype.optionNames.concat(['options']),

    cssConfig,

    /**
     * @property {jQuery}
     */
    $sourceSelect: null,

    className() {
        return this.cssConfig.main;
    },

    attributes: {
        'data-role': 'content'
    },

    /**
     * @property {function}
     */
    Model: MultiselectViewModel,

    /**
     * @property {function}
     */
    Collection: MultiSelectCollection,

    /**
     * @property {function}
     */
    CollectionView: MultiSelectCollectionView,

    /**
     * @property {function}
     */
    MultiselectHeaderView,

    /**
     * @property {function}
     */
    MultiselectFooterView,

    /**
     * @property {function}
     */
    MultiselectSearchView,

    constructor: function MultiSelectView(...args) {
        this.options = [];
        MultiSelectView.__super__.constructor.apply(this, args);
    },

    preinitialize({el, name, autoRender, ...options}) {
        if (!options.selectElement && !options.options) {
            throw new Error('Select element is required or put options collection insted');
        }

        this.$sourceSelect = options.selectElement;

        const collection = this.initializeCollection(options);

        /**
         * Create model instance
         * @type {MultiSelectViewModel}
         */
        this.model = new this.Model({
            ...options,
            cssConfig: this.cssConfig,
            collection
        });
    },

    /**
     * Initialize collection
     *
     * @param {options} param
     * @returns {MultiSelectCollection}
     */
    initializeCollection({options} = {}) {
        this.collection = new this.Collection(this.collectionItems(options));

        return this.collection;
    },

    delegateEvents(events) {
        MultiSelectView.__super__.delegateEvents.call(this, events);

        if (this.$sourceSelect) {
            /**
             * Bind change event to source select element if it is passed
             */
            this.$sourceSelect.on(`input${this.eventNamespace()}`, this.onSourceSelectChanged.bind(this));
        }

        return this;
    },

    undelegateEvents() {
        this.$sourceSelect && this.$sourceSelect.off(this.eventNamespace());

        return MultiSelectView.__super__.undelegateEvents.call(this);
    },

    /**
     * @inheritDoc
     */
    render() {
        MultiSelectView.__super__.render.call(this);

        if (this.model.get('enabledSearch')) {
            this.renderSearch();
        }

        if (this.model.get('enabledHeader')) {
            this.renderHeader();
        }

        this.renderCollection();

        if (this.model.get('enabledFooter')) {
            this.renderFooter();
        }

        /** Hide select element */
        this.$sourceSelect && this.$sourceSelect.addClass(this.cssConfig.sourceSelectHide);

        return this;
    },

    /**
     * Render multiselect items collection
     *
     * @returns {MultiSelectView}
     */
    renderCollection() {
        this.subview('multiselect-collection', new this.CollectionView(this.collectionViewRenderOptions()));
        this.listenTo(this.subview('multiselect-collection'), 'change reset', this.onCollectionChange);

        return this;
    },

    /**
     * Render header view
     *
     * @returns {MultiSelectView}
     */
    renderHeader() {
        this.subview('multiselect-header', new this.MultiselectHeaderView(this.headerRenderOptions()));

        return this;
    },

    /**
     * Render footer view
     *
     * @returns {MultiSelectView}
     */
    renderFooter() {
        this.subview('multiselect-footer', new this.MultiselectFooterView(this.footerRenderOptions()));

        return this;
    },

    renderSearch() {
        this.subview('multiselect-search', new this.MultiselectSearchView(this.searchBlockRenderOptions()));

        return this;
    },

    /**
     * Return options for options collection view
     *
     * @returns {object}
     */
    collectionViewRenderOptions() {
        return {
            container: this.getRootElement(),
            collection: this.collection,
            autoRender: true,
            model: this.model,
            cssConfig: this.cssConfig
        };
    },

    /**
     * Return options for header view
     *
     * @returns {object}
     */
    headerRenderOptions() {
        return {
            container: this.getRootElement(),
            autoRender: true,
            collection: this.collection,
            cssConfig: this.cssConfig
        };
    },

    /**
     * Return options for footer view
     *
     * @returns {object}
     */
    footerRenderOptions() {
        return {
            container: this.getRootElement(),
            autoRender: true,
            collection: this.collection,
            cssConfig: this.cssConfig
        };
    },

    /**
     * Return options for search block view
     *
     * @returns {object}
     */
    searchBlockRenderOptions() {
        return {
            container: this.getRootElement(),
            autoRender: true,
            collection: this.collection,
            cssConfig: this.cssConfig,
            maxItemsForShowSearchBar: this.model.get('maxItemsForShowSearchBar')
        };
    },

    /**
     * Return root element of the view
     *
     * @returns {jQuery}
     */
    getRootElement() {
        return this.$el;
    },

    /**
     * Resolve initial options for collection depending on passed options or select element
     * If options are passed, they will be used as initial state for collection
     * If options are not passed, select element will be used to get options
     *
     * @param {object} options - options as items for collection
     * @returns
     */
    collectionItems(options) {
        if (options) {
            return options;
        } else if (this.$sourceSelect) {
            return this.getSelectOptions(this.$sourceSelect.get(0));
        } else {
            return [];
        }
    },

    /**
     * Get options from select element
     *
     * @param {HTMLSelectElement} select
     * @returns {Array<Object>} Array of option objects with properties:
     *   - {string} label - The inner HTML text of the option
     *   - {boolean} selected - Whether the option is selected
     *   - {boolean} disabled - Whether the option is disabled
     *   - {string} value - The value of the option
     *   - {string} id - The ID of the option or generated ID in format 'selectable-item-{value}'
     */
    getSelectOptions(select) {
        return [...select.options].map(option => ({
            label: option.innerHTML,
            selected: option.selected,
            disabled: option.disabled,
            value: option.value,
            id: option.id || MultiSelectItemModel.getAlias(option.value)
        }));
    },

    /**
     * Update current collection state from source select if it presset
     *
     * @param {InputEvent} event
     */
    onSourceSelectChanged(event) {
        this.subview('multiselect-collection').setState(this.getSelectOptions(event.currentTarget));
    },

    /**
     * Sync with source select from the current collection state
     * If source select is present
     *
     * @param {MultiSelectCollection} collection
     */
    onCollectionChange(collection) {
        this.$sourceSelect && this.$sourceSelect.val(collection.getSelectedValues()).trigger('change');
    },

    /**
     * Toggle for refreshing the view
     * It will re-render the view and update the state of the collection
     */
    refresh() {
        this.setState(this.collectionItems(this.model.get('options')));
    },

    /**
     * Get the state of the collection
     * It will return the state of the collection
     *
     * @returns {Array<Object>} Array of option objects with properties:
     *   - {string} label - The inner HTML text of the option
     *   - {boolean} selected - Whether the option is selected
     *   - {boolean} disabled - Whether the option is disabled
     *   - {string} value - The value of the option
     *   - {string} id - The ID of the option or generated ID in format 'selectable-item-{value}'
     */
    getState() {
        return this.subview('multiselect-collection').getState();
    },

    /**
     * Set the state of the collection
     * It will set the state of the collection
     *
     * @param {Array<Object>} state - Array of option objects with properties:
     *   - {string} label - The inner HTML text of the option
     *   - {boolean} selected - Whether the option is selected
     *   - {boolean} disabled - Whether the option is disabled
     *   - {string} value - The value of the option
     *   - {string} id - The ID of the option or generated ID in format 'selectable-item-{value}'
     */
    setState(state = []) {
        this.subview('multiselect-collection').setState(state);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.$sourceSelect && this.$sourceSelect.removeClass(this.cssConfig.sourceSelectHide);

        MultiSelectView.__super__.dispose.call(this);
    }
});

export default MultiSelectView;
