define(function(require) {
    'use strict';

    var $ = require('jquery');
    var RoutingCollection = require('./base/routing-collection');

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
    var LoadMoreCollection;

    LoadMoreCollection = RoutingCollection.extend(/** @lends LoadMoreCollection.prototype */{
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
         * @inheritDoc
         */
        constructor: function LoadMoreCollection(models, options) {
            LoadMoreCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(models, options) {
            LoadMoreCollection.__super__.initialize.call(this, models, options);

            this.initialLimit = this._route.get(this.limitPropertyName) || this.initialLimit;
        },

        /**
         * @inheritDoc
         */
        parse: function(response) {
            if (!this.disposed) {
                this._state.set('totalItemsQuantity', response.count || 0);
            }
            return LoadMoreCollection.__super__.parse.apply(this, arguments);
        },

        /**
         * Loads additional state.loadMoreItemsQuantity items to this collection
         * @returns {$.Promise} promise
         */
        loadMore: function() {
            var loadDeferred;
            var limit = this._route.get(this.limitPropertyName) + this._state.get('loadMoreItemsQuantity');

            this._route.set(this.limitPropertyName, limit);
            loadDeferred = $.Deferred();
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
            var total = this._state.get('totalItemsQuantity');

            return total === void 0 || this.length < total;
        },

        /**
         * @inheritDoc
         */
        reset: function() {
            this._route.set(this.limitPropertyName, this.initialLimit, {silent: true});

            return LoadMoreCollection.__super__.reset.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
         * @inheritDoc
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
