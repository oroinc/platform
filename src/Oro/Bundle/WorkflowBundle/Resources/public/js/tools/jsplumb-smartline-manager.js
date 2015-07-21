define(function(require){
    'use strict';
    require('./path-finder');
    var JsPlumbOverlayManager = require('./jsplumb-overlay-manager');

    function matchEndpoints(paintInfo1, paintInfo2) {
        var keys = ['sx', 'sy', 'tx', 'ty'];
        return _.isEqual(_.pick(paintInfo1, keys), _.pick(paintInfo2, keys));
    }

    function JsPlumbSmartlineManager(jsPlumbInstance) {
        this.jsPlumbInstance = jsPlumbInstance;
        this.jsPlumbOverlayManager = new JsPlumbOverlayManager(this);
        this.cache = {};
        this.debouncedCalculateOverlays = _.debounce(
            _.bind(this.jsPlumbOverlayManager.calculate, this.jsPlumbOverlayManager),
        50);
    }

    window.getLastRequest = function () {
        return JSON.stringify(JsPlumbSmartlineManager.lastRequest);
    }

    JsPlumbSmartlineManager.prototype = {

        isCacheValid: function () {
            return _.isEqual(this.getState(), this.cache.state)
        },

        getConnectionPath: function (connector) {
            if (!this.isCacheValid()) {
                this.refreshCache();
            }

            return this.cache.connections[connector.getId()] || [];
        },

        getNaivePathLength: function (fromRect, toRect) {
            if (fromRect == toRect) {
                return 0;
            }
            return Math.abs(fromRect.bottom - toRect.top)
                + Math.max(0, fromRect.left - toRect.right, toRect.left - fromRect.right)
                + ((fromRect.bottom - toRect.top > 0) ? 1200 : 0);
        },


        getState: function () {
            var state = {
                rectangles: [],
                connections: []
            }
            _.each(this.jsPlumbInstance.sourceEndpointDefinitions, function(endPoint, id) {
                var el = document.getElementById(id);
                if (el) {
                    state.rectangles.push([id, el.offsetLeft, el.offsetTop, el.offsetWidth, el.offsetHeight]);
                }
            });

            _.each(this.jsPlumbInstance.getConnections(), function (conn) {
                if (conn.sourceId in rects && conn.targetId in rects) {
                    state.connections.push([conn.sourceId, conn.targetId]);
                }
            }, this);

            return state;
        },

        refreshCache: function () {
            this.cache.state = this.getState();
            var cache = {};
            var connections = [];
            var rects = {};
            var graph = new Graph();

            _.each(this.jsPlumbInstance.sourceEndpointDefinitions, function(endPoint, id) {
                var clientRect;
                var el = document.getElementById(id);
                if (el) {
                    clientRect = new Rectangle(el.offsetLeft, el.offsetTop, el.offsetWidth, el.offsetHeight);
                    rects[id] = clientRect;
                    clientRect.cid = id;
                    graph.rectangles.push(clientRect);
                }
            });

            if (graph.rectangles.length < 1) {
                this.cache.connections = {};
                return;
            }

            graph.build();

            _.each(this.jsPlumbInstance.getConnections(), function (conn) {
                if (conn.sourceId in rects && conn.targetId in rects) {
                    connections.push([conn.sourceId, conn.targetId, this.getNaivePathLength(rects[conn.sourceId], rects[conn.targetId]), conn]);
                }
            }, this);

            connections.sort(function (a, b) {
                return a[2] - b[2];
            });

            JsPlumbSmartlineManager.lastRequest = {
                sources: graph.rectangles.map(function (item) {
                    return [item.cid, item.left, item.top, item.width, item.height];
                }),
                connections: connections.map(function (item) {return item.slice(0,2);})
            };

            _.each(connections, function (conn) {
                var finder = new Finder(graph);

                finder.addTo(graph.getPathFromCid(conn[1], Direction2d.BOTTOM_TO_TOP));
                finder.addFrom(graph.getPathFromCid(conn[0], Direction2d.TOP_TO_BOTTOM));

                var newCacheItem;
                var path = finder.find();
                var cacheKey = conn[3].connector.getId();
                if (!path) {
                    console.warn("Cannot find path");
                } else {
                    graph.updateWithPath(path);
                    newCacheItem = {
                        connection: conn[3],
                        path: path,
                        paintInfo: conn[3].connector === connector ? paintInfo : undefined
                    };
                    if (conn[3].connector === connector) {
                        newCacheItem.paintInfo = paintInfo;
                    }
                    cache[cacheKey] = newCacheItem;
                }
            });

            _.each(cache, function (item) {
                var points =  item.path.points.reverse();
                item.points = points;
            });

            this.jsPlumbInstance.repaintEverything();
            this.debouncedCalculateOverlays();
        }
    };

    return JsPlumbSmartlineManager;
});
