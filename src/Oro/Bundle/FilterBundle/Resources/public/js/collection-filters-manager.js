/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './filters-manager'
], function (_, FiltersManager) {
    'use strict';

    var CollectionFiltersManager;

    /**
     * View that represents all grid filters
     *
     * @export  orofilter/js/collection-filters-manager
     * @class   orofilter.CollectionFiltersManager
     * @extends orofilter.FiltersManager
     */
    CollectionFiltersManager = FiltersManager.extend({
        /**
         * Initialize filter list options
         *
         * @param {Object} options
         * @param {oro.PageableCollection} [options.collection]
         * @param {Object} [options.filters]
         * @param {String} [options.addButtonHint]
         */
        initialize: function (options) {
            this.collection = options.collection;

            this.listenTo(this.collection, {
                'beforeFetch': this._beforeCollectionFetch,
                'updateState': this._onUpdateCollectionState,
                'reset': this._onCollectionReset
            });

            CollectionFiltersManager.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            CollectionFiltersManager.__super__.render.apply(this, arguments);
            this._onUpdateCollectionState(this.collection);
            return this;
        },

        /**
         * Triggers when filter is updated
         *
         * @param {oro.filter.AbstractFilter} filter
         * @protected
         */
        _onFilterUpdated: function (filter) {
            if (this.ignoreFiltersUpdateEvents) {
                return;
            }
            this.collection.state.currentPage = 1;
            this.collection.fetch({reset: true});

            CollectionFiltersManager.__super__._onFilterUpdated.apply(this, arguments);
        },

        /**
         * Triggers before collection fetch it's data
         *
         * @protected
         */
        _beforeCollectionFetch: function (collection) {
            collection.state.filters = this._createState();
        },

        /**
         * Triggers when collection state is updated
         *
         * @param {oro.PageableCollection} collection
         */
        _onUpdateCollectionState: function (collection) {
            this.ignoreFiltersUpdateEvents = true;
            this._applyState(collection.state.filters || {});
            this.ignoreFiltersUpdateEvents = false;
        },

        /**
         * Triggers after collection resets it's data
         *
         * @protected
         */
        _onCollectionReset: function (collection) {
            var hasRecords, hasFiltersState;
            hasRecords = collection.state.totalRecords > 0;
            hasFiltersState = !_.isEmpty(collection.state.filters);
            if (hasRecords || hasFiltersState) {
                this.$el.show();
            } else {
                this.$el.hide();
            }
        },

        /**
         * Create state according to filters parameters
         *
         * @return {Object}
         * @protected
         */
        _createState: function () {
            var state = {};
            _.each(this.filters, function (filter, name) {
                var shortName = '__' + name;
                if (filter.enabled) {
                    if (!filter.isEmpty()) {
                        state[name] = filter.getValue();
                    } else if (!filter.defaultEnabled) {
                        state[shortName] = 1;
                    }
                } else if (filter.defaultEnabled) {
                    state[shortName] = 0;
                }
            }, this);

            return state;
        },

        /**
         * Apply filter values from state
         *
         * @param {Object} state
         * @protected
         * @return {*}
         */
        _applyState: function (state) {
            var toEnable  = [],
                toDisable = [];

            _.each(this.filters, function (filter, name) {
                var shortName = '__' + name,
                    filterState;
                if (_.has(state, name)) {
                    filterState = state[name];
                    if (!_.isObject(filterState)) {
                        filterState = {
                            value: filterState
                        };
                    }
                    filter.setValue(filterState);
                    toEnable.push(filter);
                } else if (_.has(state, shortName)) {
                    filter.reset();
                    if (Number(state[shortName])) {
                        toEnable.push(filter);
                    } else {
                        toDisable.push(filter);
                    }
                } else {
                    filter.reset();
                    if (filter.defaultEnabled) {
                        toEnable.push(filter);
                    } else {
                        toDisable.push(filter);
                    }
                }
            }, this);

            this.enableFilters(toEnable);
            this.disableFilters(toDisable);

            return this;
        }
    });

    return CollectionFiltersManager;
});
