define([
    'underscore',
    'backbone',
    'oroui/js/mediator',
    'orodatagrid/js/pageable-collection'
], function(_, Backbone, mediator, PageableCollection) {
    'use strict';

    var contentManager;

    function updateState(collection) {
        var key = collection.stateHashKey();
        var hash = collection.stateHashValue(true);
        mediator.execute('pageCache:state:save', key, collection.clone(), hash);
    }

    contentManager = {
        get: function(gridName) {
            var hash;
            var isActual;
            var key = PageableCollection.stateHashKey(gridName);
            var collection = mediator.execute('pageCache:state:fetch', key);
            if (collection) {
                hash = collection.stateHashValue(true);
                // check if collection reflects grid state in url
                isActual = mediator.execute('pageCache:state:check', key, hash);
                collection = isActual ? collection.clone() : undefined;
            }
            return collection;
        },

        trace: function(collection) {
            updateState(collection);
            contentManager.listenTo(collection, {
                updateState: updateState,
                beforeReset: updateState
            });
            mediator.once('page:beforeChange', function() {
                contentManager.stopListening(collection);
            });
        }
    };
    _.extend(contentManager, Backbone.Events);

    return contentManager;
});
