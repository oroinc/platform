/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'oroui/js/mediator',
    'oroui/js/tools'
], function (_, Backbone, mediator, tools) {
    'use strict';

    var contentManager, stateShortKeys, executeAction;

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

    executeAction = false;

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

    function addHashToUrl(grid, action)
    {
        // only links after execute event must be modified
        action.on('preExecute', function () {
            executeAction = true;
        });
        action.on('getLink', function (action, linkData) {
            if (executeAction) {
                linkData.link = action.addUrlParameter(
                    linkData.link,
                    gridNameKey(grid.name),
                    encodeStateData(grid.collection.state)
                );
                executeAction = false;
            }
        });
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
        },

        process: function (grid) {
            if (grid.rowClickAction) {
                addHashToUrl(grid, grid.rowClickAction.prototype);
            }

            _.each(grid.body.rows, function (row) {
                _.each(row.cells, function (cell) {
                    if (cell.actions) {
                        _.each(cell.actions, function (action) {
                            addHashToUrl(grid, action);
                        });
                    }
                });
            });
        }
    };
    _.extend(contentManager, Backbone.Events);

    return contentManager;
});
