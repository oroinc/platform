import {uniq} from 'underscore';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import MultiSelectCollection from 'oroui/js/app/views/multiselect/collection/multiselect-collection';
import MultiSelectItemView, {cssConfig as itemCssConfig}
    from 'oroui/js/app/views/multiselect/collection/multiselect-item-view';
import manageFocus from 'oroui/js/tools/manage-focus';
import KEY_CODES from 'oroui/js/tools/keyboard-key-codes';
import notFoundTemplate from 'tpl-loader!oroui/templates/multiselect/collection/not-found.html';

export const cssConfig = {
    list: 'multiselect__list',
    listOffset: 'multiselect--offset',
    ...itemCssConfig
};

/**
 * Multiselect collection view
 * It is used to render multiselect component
 *
 * @class MultiSelectCollectionView
 */
const MultiSelectCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat([
        'initialOptions', 'MultiselectHeaderView', 'MultiselectFooterView',
        'cssConfig', 'preventTabOutOfContainer'
    ]),

    cssConfig,

    itemView: MultiSelectItemView,

    className() {
        const classes = [this.cssConfig.list];

        if (this.model.get('enabledHeader') === true) {
            classes.push(this.cssConfig.listOffset);
        }

        return uniq(classes).join(' ');
    },

    events: {
        keydown: 'onKeyDown'
    },

    listen: {
        'all collection': 'onCollectionEvent',
        'visibilityModelsChange collection': 'filter',
        'visibilityChange': 'toggleNotToShowBlock'
    },

    constructor: function MultiSelectCollectionView(...args) {
        this.initialOptions = [];
        MultiSelectCollectionView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        if (!options.collection) {
            options.collection = new MultiSelectCollection(options.initialOptions);
        }

        MultiSelectCollectionView.__super__.initialize.call(this, options);
    },

    initItemView(model) {
        if (this.itemView) {
            return new this.itemView({
                autoRender: false,
                model: model,
                cssConfig: this.cssConfig
            });
        } else {
            MultiSelectCollectionView.__super__.initItemView.call(this, model);
        }
    },

    /**
     * Render collection view
     *
     * @returns {MultiSelectCollectionView}
     */
    renderAllItems() {
        /** Keep scroll */
        const scrollTop = this.$list.prop('scrollTop');

        MultiSelectCollectionView.__super__.renderAllItems.call(this);

        /** Restore scroll */
        this.$list.prop('scrollTop', scrollTop);

        return this;
    },

    /**
     * Set state of the collection
     */
    setState(state, options = {}) {
        this.collection.setState(state, options);
    },

    /**
     * Get current state of the collection
     */
    getState() {
        return this.collection.toJSON();
    },

    /**
     * Proxy collection events to view
     */
    onCollectionEvent(eventName, ...args) {
        this.trigger(eventName, this.collection, eventName, ...args);
    },

    /**
     * Filter items in the collection based on the 'hidden' attribute.
     *
     * @param {MultiSelectModel} item
     * @returns {boolean}
     */
    filterer(item) {
        return !item.get('hidden');
    },

    /**
     * Toggle visibility of items in the collection based on the filter.
     *
     * @param {MultiSelectItemView} view
     * @param {boolean} included
     */
    filterCallback(view, included) {
        view.toggleVisibility(!included);
    },

    /**
     * Show or hide "not found" block based on the visible items in the collection.
     *
     * @param {Array[MultiSelectModel]} visibleItems
     */
    toggleNotToShowBlock(visibleItems) {
        if (visibleItems.length === 0) {
            !this.$('[data-role="no-data"]').length && this.$el.append(notFoundTemplate(this.model.toJSON()));
        } else {
            this.$('[data-role="no-data"]').remove();
        }
    },

    /**
     * @inheritDoc
     */
    render() {
        MultiSelectCollectionView.__super__.render.call(this);

        this.toggleNotToShowBlock(this.visibleItems);

        return this;
    },

    getViewByModel(model) {
        const models = this.getItemViews();
        if (model.cid in models) {
            return models[model.cid];
        }

        return null;
    },

    /**
     * Return model by target dom element
     *
     * @param {HTMLInputElement} target
     * @returns {MultiSelectModel|null}
     */
    getModelByTarget(target) {
        return this.collection.get(this.collection.model.getAlias(target.value));
    },

    /**
     * Focus on the specified element inside the collection view.
     *
     * @param {HTMLElement|jQuery} target
     */
    focusOnElement(target) {
        manageFocus.focusTabbable(this.$el, target instanceof this.$ ? target : this.$(target));
    },

    /**
     * Handle user keyboard navigation within the collection view.
     *
     * @param {KeyboardEvent} event
     */
    moveFocus(event) {
        event.preventDefault();

        const view = this.getViewByModel(this.getModelByTarget(event.target));
        const tabbableElements = this.$(':visible:tabbable').toArray();

        switch (event.which) {
            case KEY_CODES.ARROW_DOWN:
            case KEY_CODES.ARROW_RIGHT:
                const nextElement = manageFocus.getNextTabbable(tabbableElements, view.getActiveElement());

                if (nextElement) {
                    this.focusOnElement(nextElement);
                } else {
                    const first = manageFocus.getFirstTabbable(tabbableElements);

                    if (first) {
                        this.focusOnElement(first);
                    }
                }

                break;
            case KEY_CODES.ARROW_UP:
            case KEY_CODES.ARROW_LEFT:
                const prevElement = manageFocus.getPrevTabbable(tabbableElements, view.getActiveElement());
                if (prevElement) {
                    this.focusOnElement(prevElement);
                } else {
                    const last = manageFocus.getLastTabbable(tabbableElements);

                    if (last) {
                        this.focusOnElement(last);
                    }
                }
                break;
            default:
        }
    },

    /**
     * Handle common keyboard events for the collection view.
     *
     * @param {KeyboardEvent} event
     */
    onKeyDown(event) {
        const currentModel = this.getModelByTarget(event.target);

        switch (event.which) {
            case KEY_CODES.ARROW_UP:
            case KEY_CODES.ARROW_DOWN:
            case KEY_CODES.ARROW_LEFT:
            case KEY_CODES.ARROW_RIGHT:
                this.moveFocus(event);
                break;
            case KEY_CODES.ENTER:
            case KEY_CODES.SPACE:
                event.preventDefault();
                if (currentModel) {
                    currentModel.toggleSelectedState();
                }
                break;
            case KEY_CODES.A:
                if (event.altKey) {
                    this.collection.selectAll();
                }
                break;
            case KEY_CODES.U:
                if (event.altKey) {
                    this.collection.unSelectAll();
                }
                break;
        }
    }
});

export default MultiSelectCollectionView;
