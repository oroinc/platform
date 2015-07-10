/** @lends LoadMoreCollection */
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

    LoadMoreCollection = RoutingCollection.extend(/** @exports LoadMoreCollection.prototype */{
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
        parse: function(response) {
            this._state.set('totalItemsQuantity', response.count || 0);
            return LoadMoreCollection.__super__.parse.apply(this, arguments);
        },

        /**
         * Loads additional state.loadMoreItemsQuantity items to this collection
         * @returns {$.Promise} promise
         */
        loadMore: function() {
            var loadDeferred;
            this._route.set({
                limit: this._route.get('limit') + this._state.get('loadMoreItemsQuantity')
            });
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
         * @inheritDoc
         */
        _onAdd: function() {
            // ignore add events during syncing
            if (this.isSyncing()) {
                return;
            }
            this._route.set({
                limit: this._route.get('limit') + 1
            }, {silent: true});
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
            this._route.set({
                limit: this._route.get('limit') - 1
            }, {silent: true});
            this._state.set({
                totalItemsQuantity: this._state.get('totalItemsQuantity') - 1
            });
        }
    });

    return LoadMoreCollection;
});
