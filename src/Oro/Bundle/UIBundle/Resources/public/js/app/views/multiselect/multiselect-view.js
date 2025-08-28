import {omit} from 'underscore';
import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import MultiSelectCollectionView, {cssConfig as collectionCssConfig}
    from 'oroui/js/app/views/multiselect/collection/multiselect-collection-view';
import MultiselectViewModel from 'oroui/js/app/views/multiselect/models/multiselect-view-model';
import MultiSelectCollection from 'oroui/js/app/views/multiselect/collection/multiselect-collection';
import MultiselectHeaderView, {cssConfig as headerCssConfig}
    from 'oroui/js/app/views/multiselect/parts/header/multiselect-header-view';
import MultiselectFooterView, {cssConfig as footerCssConfig}
    from 'oroui/js/app/views/multiselect/parts/footer/multiselect-footer-view';
import MultiselectSearchView, {cssConfig as searchCssConfig}
    from 'oroui/js/app/views/multiselect/parts/search/multiselect-search-view';

export const cssConfig = {
    main: 'multiselect-view',
    sourceSelectHide: 'hide',
    ...collectionCssConfig,
    ...headerCssConfig,
    ...footerCssConfig,
    ...searchCssConfig
};

/**
 * Multiselect view
 * It is used to render multiselect component
 *
 * @class MultiSelectView
 */
const MultiSelectView = BaseMultiSelectView.extend({
    optionNames: BaseMultiSelectView.prototype.optionNames.concat(['options', 'defaultOptions']),

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

    preinitialize(options) {
        if (!options.selectElement && !options.options) {
            throw new Error('Select element is required or put options collection insted');
        }

        this.$sourceSelect = options.selectElement;
        this.subViewOptions = omit(options, this.optionNames);

        return MultiSelectView.__super__.preinitialize.call(this, options);
    },

    delegateEvents(events) {
        MultiSelectView.__super__.delegateEvents.call(this, events);

        if (this.$sourceSelect) {
            /**
             * Bind change event to source select element if it is passed
             */
            this.$sourceSelect.on(
                `input${this.eventNamespace()} change${this.eventNamespace()}`,
                this.onSourceSelectChanged.bind(this)
            );
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

    resetSearch() {
        if (this.subview('multiselect-search')) {
            this.subview('multiselect-search').clearSearchInput();
        }
    },

    getCollectionOptions() {
        return {
            defaultState: this.defaultOptions
        };
    },

    /**
     * Render multiselect items collection
     *
     * @returns {MultiSelectView}
     */
    renderCollection() {
        this.subview('multiselect-collection', new this.CollectionView(this.collectionViewRenderOptions()));
        this.listenTo(this.subview('multiselect-collection'), 'change:selected reset', this.onCollectionChange);

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
     * Return common options for subview
     *
     * @returns {object}
     */
    getCommonSubViewOptions() {
        return {
            ...this.subViewOptions,
            container: this.getRootElement(),
            collection: this.collection,
            autoRender: true,
            cssConfig: this.cssConfig
        };
    },

    /**
     * Return options for options collection view
     *
     * @returns {object}
     */
    collectionViewRenderOptions() {
        return {
            ...this.getCommonSubViewOptions(),
            model: this.model
        };
    },

    /**
     * Return options for header view
     *
     * @returns {object}
     */
    headerRenderOptions() {
        return this.getCommonSubViewOptions();
    },

    /**
     * Return options for footer view
     *
     * @returns {object}
     */
    footerRenderOptions() {
        return this.getCommonSubViewOptions();
    },

    /**
     * Return options for search block view
     *
     * @returns {object}
     */
    searchBlockRenderOptions() {
        return this.getCommonSubViewOptions();
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
            return MultiSelectView.__super__.collectionItems.call(this, options);
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
        return [...select.children].map(option => {
            if (option instanceof HTMLOptionElement) {
                const data = option.dataset;
                const opts = {};
                for (const key in data) {
                    if (key.startsWith('option')) {
                        opts[key] = data[key];
                    }
                }

                return {
                    label: option.innerHTML,
                    selected: option.selected,
                    disabled: option.disabled,
                    ...opts,
                    value: option.value
                };
            }

            if (option instanceof HTMLOptGroupElement) {
                return {
                    type: 'optgroup',
                    label: option.label,
                    value: option.label.toLowerCase().replace(/\s/, '_'),
                    options: this.getSelectOptions(option)
                };
            }
        });
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
        this.trigger('change:selected', collection.getSelectedValues());
    },

    /**
     * Toggle for refreshing the view
     * It will re-render the view and update the state of the collection
     */
    refresh() {
        this.setState(null);
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
        this.subview('multiselect-collection').setState(this.collectionItems(state));
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
