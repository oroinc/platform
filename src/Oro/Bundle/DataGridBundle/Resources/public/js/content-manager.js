/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'oroui/js/mediator',
    'oroui/js/tools'
], function (_, Backbone, mediator, tools) {
    'use strict';

    var contentManager, stateShortKeys;

    /**
     * Object declares state keys that will be involved in URL-state saving with their shorthands
     *
     * @property {Object}
     */
    stateShortKeys = {
        currentPage: 'i',
        pageSize: 'p',
        sorters: 's',
        filters: 'f',
        gridView: 'v'
    };

    /**
     * Encode state object to string
     *
     * @param {Object} stateObject
     * @return {string}
     */
    function encodeStateData(stateObject) {
        var data = _.pick(stateObject, _.keys(stateShortKeys));
        data = tools.invertKeys(data, stateShortKeys);
        return tools.packToQueryString(data);
    }

    /**
     * Decode state object from string, operation is invert for encodeStateData.
     *
     * @param {string} stateString
     * @return {Object}
     */
    function decodeStateData(stateString) {
        var data = tools.unpackFromQueryString(stateString);
        data = tools.invertKeys(data, _.invert(stateShortKeys));
        return data;
    }

    function gridNameKey(gridName) {
        return 'grid[' + gridName + ']';
    }

    function stateHash(collection) {
        var hash;
        hash = encodeStateData(collection.state);
        if (hash === encodeStateData(collection.initialState)) {
            // if the state is the same as initial, remove URL param for grid state
            hash = null;
        }
        return hash;
    }

    function updateState(collection) {
        var gridName, key, hash;
        gridName = collection.inputName;
        key = gridNameKey(gridName);
        hash = stateHash(collection);
        mediator.execute('pageCache:state:save', key, collection.clone(), hash);
    }

    contentManager = {
        get: function (gridName) {
            var key, collection, hash, isActual;
            key = gridNameKey(gridName);
            collection = mediator.execute('pageCache:state:fetch', key);
            if (collection) {
                hash = stateHash(collection);
                // check if collection reflects grid state in url
                isActual = mediator.execute('pageCache:state:check', key, hash);
                collection = isActual ? collection.clone() : undefined;
            }
            return collection;
        },

        trace: function (collection) {
            updateState(collection);
            contentManager.listenTo(collection, 'beforeReset', updateState);
            mediator.once('page:beforeChange', function () {
                contentManager.stopListening(collection);
            });
        }
    };
    _.extend(contentManager, Backbone.Events);

    return contentManager;
});
