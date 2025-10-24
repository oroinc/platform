import {uniq} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import BaseModel from 'oroui/js/app/models/base/model';
import BaseCollection from 'oroui/js/app/models/base/collection';

const BaseMultiSelectView = BaseView.extend({
    Model: BaseModel,

    Collection: BaseCollection,

    constructor: function BaseMultiSelectView(options, ...args) {
        if (options.cssConfig) {
            this.cssConfig = BaseMultiSelectView.cssMergeConfig(options.cssConfig, this.cssConfig || {});
        }

        BaseMultiSelectView.__super__.constructor.apply(this, [options, ...args]);
    },

    preinitialize(options) {
        const collection = this.initializeCollection(options);

        /**
         * Create model instance
         * @type {Model}
         */
        this.model = new this.Model({
            collection,
            ...this.getModelInitOptions(options),
            cssConfig: this.cssConfig
        });

        return options;
    },

    getModelInitOptions(options = {}) {
        return options;
    },

    /**
     * Initialize collection
     *
     * @param {options} param
     * @returns {MultiSelectCollection}
     */
    initializeCollection({options} = {}) {
        this.collection = new this.Collection(this.collectionItems(options), this.getCollectionOptions());

        return this.collection;
    },

    collectionItems(options) {
        return options;
    },

    getCollectionOptions() {
        return {};
    },

    /**
     * Return root element of the view
     *
     * @returns {jQuery}
     */
    getRootElement() {
        return this.$el;
    }
}, {
    /**
     * Merge css config for views
     *
     * @param {object} origin
     * @param {object} source
     * @returns {object}
     */
    cssMergeConfig(origin, source) {
        if (origin.strategy !== 'override') {
            for (const [key, value] of Object.entries(source)) {
                if (key in origin) {
                    source[key] = uniq([...value.split(' '), ...origin[key].split(' ')]).join(' ');
                }
            }

            return source;
        } else {
            return {
                ...source,
                ...origin
            };
        }
    }
});

export default BaseMultiSelectView;
