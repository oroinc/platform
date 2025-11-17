import _ from 'underscore';
import Backbone from 'backbone';
import mediator from 'oroui/js/mediator';
import PageableCollection from 'orodatagrid/js/pageable-collection';
import GridViewsCollection from 'orodatagrid/js/datagrid/grid-views/collection';

function updateState(collection) {
    const key = collection.stateHashKey();
    const hash = collection.stateHashValue(true);
    mediator.execute('pageCache:state:save', key, collection.clone(), hash);
}

const contentManager = {
    /**
     * Fetches grid collection from page cache storage
     *
     * @param {string} gridName
     */
    get: function(gridName) {
        let hash;
        let isActual;
        const key = PageableCollection.stateHashKey(gridName);
        let collection = mediator.execute('pageCache:state:fetch', key);
        if (collection) {
            hash = collection.stateHashValue(true);
            // check if collection reflects grid state in url
            isActual = mediator.execute('pageCache:state:check', key, hash);
            collection = isActual ? collection.clone() : undefined;
        }
        return collection;
    },

    /**
     * Trace grid collection changes and update it's state in page cache
     *
     * @param {PageableCollection} collection
     */
    trace: function(collection) {
        updateState(collection);
        contentManager.listenTo(collection, {
            updateState: updateState,
            reset: updateState
        });
        mediator.once('page:beforeChange', function() {
            contentManager.stopListening(collection);
        });
    },

    /**
     * Fetches grid views collection from page cache storage
     *
     * @param {string} gridName
     */
    getViewsCollection: function(gridName) {
        const key = GridViewsCollection.stateHashKey(gridName);
        return mediator.execute('pageCache:state:fetch', key);
    },

    /**
     * Trace grid views collection changes and update it's state in page cache
     *
     * @param {GridViewsCollection} collection
     */
    traceViewsCollection: function(collection) {
        updateState(collection);
        contentManager.listenTo(collection, {'reset add remove': function() {
            updateState(collection);
        }});
        mediator.once('page:beforeChange', function() {
            contentManager.stopListening(collection);
        });
    }
};
_.extend(contentManager, Backbone.Events);

export default contentManager;
