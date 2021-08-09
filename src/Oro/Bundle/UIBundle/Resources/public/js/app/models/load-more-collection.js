define(function(require) {
    'use strict';

    const $ = require('jquery');
    const RoutingCollection = require('./base/routing-collection');

    /**
     * Collection with "load more" functionality support. Any add/remove actions will be considered like already done
     * on the server and collection will update `state.totalItemsQuantity` and `route.limit`
     *
     * Requires API route which accepts `limit` query parameter
     *
     * @class
     * @augment RoutingCollection
     * @exports LoadMoreCollection
     */
    const LoadMoreCollection = RoutingCollection.extend(/** @lends LoadMoreCollection.prototype */{
        limitPropertyName: 'limit',

        initialLimit: 0,

        routeDefaults: {
            /**
             * Initial quantity of items to load
             * @type {number}
             */
            limit: 5
        },

        stateDefaults: {
            /**
             * Quantity of extra items to load on loadMore() call
             * @type {number}
             */
            loadMoreItemsQuantity: 10,

            /**
             * Contains quantity of items on server
             */
            totalItemsQuantity: 0
        },

        /**
         * @inheritdoc
         */
        constructor: function LoadMoreCollection(models, options) {
            LoadMoreCollection.__super__.constructor.call(this, models, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            LoadMoreCollection.__super__.initialize.call(this, models, options);

            this.initialLimit = this._route.get(this.limitPropertyName) || this.initialLimit;
        },

        /**
         * @inheritdoc
         */
        parse: function(response) {
            if (!this.disposed) {
                this._state.set('totalItemsQuantity', response.count || 0);
            }
            return LoadMoreCollection.__super__.parse.call(this, response);
        },

        /**
         * Loads additional state.loadMoreItemsQuantity items to this collection
         * @returns {$.Promise} promise
         */
        loadMore: function() {
            const limit = this._route.get(this.limitPropertyName) + this._state.get('loadMoreItemsQuantity');

            this._route.set(this.limitPropertyName, limit);
            const loadDeferred = $.Deferred();
            if (this.isSyncing()) {
                this.once('sync', function() {
                    loadDeferred.resolve(this);
                });
            } else {
                loadDeferred.resolve(this);
            }
            return loadDeferred.promise();
        },

        /**
         * Checks if more unloaded items are available
         *
         * @returns {boolean}
         */
        hasMore: function() {
            const total = this._state.get('totalItemsQuantity');

            return total === void 0 || this.length < total;
        },

        /**
         * @inheritdoc
         */
        reset: function(...args) {
            this._route.set(this.limitPropertyName, this.initialLimit, {silent: true});

            return LoadMoreCollection.__super__.reset.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        _onAdd: function() {
            // ignore add events during syncing
            if (this.isSyncing()) {
                return;
            }
            this._route.set(this.limitPropertyName, this._route.get(this.limitPropertyName) + 1, {silent: true});
            this._state.set({
                totalItemsQuantity: this._state.get('totalItemsQuantity') + 1
            });
        },

        /**
         * @inheritdoc
         */
        _onRemove: function() {
            // ignore remove events during syncing
            if (this.isSyncing()) {
                return;
            }
            this._route.set(this.limitPropertyName, this._route.get(this.limitPropertyName) - 1, {silent: true});
            this._state.set({
                totalItemsQuantity: this._state.get('totalItemsQuantity') - 1
            });
        }
    });

    return LoadMoreCollection;
});
