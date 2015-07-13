define(function(require){
    'use strict';
    require('./path-finder');
    function JsPlumbSmartlineManager(jsPlumb) {
        this.jsPlumb = jsPlumb;
    }

    window.getLastRequest = function () {
        return JSON.stringify(JsPlumbSmartlineManager.lastRequest);
    }

    JsPlumbSmartlineManager.prototype = {
        getNaivePathLength: function (fromRect, toRect) {
            return Math.abs(fromRect.bottom - toRect.top)
                + Math.max(0, fromRect.left - toRect.right, toRect.left - fromRect.right)
                + ((fromRect.bottom - toRect.top > 0) ? 2400 : 0);
        },

        calculate: function () {
            var _this = this,
                rects = {},
                sources = new Container();
            var endPoints = this.jsPlumb.instance.sourceEndpointDefinitions;
            for (var id in endPoints) {
                if (endPoints.hasOwnProperty(id)) {
                    var el = document.getElementById(id);
                    if (!el) {
                        this.cache = [];
                        return;
                    }
                    var rect = el.getBoundingClientRect(),
                        clientRect = new Rectangle(rect.left - 16, rect.top - 16, rect.width + 32, rect.height+ 32);
                    rects[id] = clientRect;
                    //console.log(rect);
                    clientRect.cid = id;
                    sources.boxes.push(clientRect);
                }
            }
            var builder = new GraphBuilder();
            var graph = builder.build(sources);
            var cache = [];

            var connections = [];

            _.each(this.jsPlumb.instance.getConnections(), function (conn) {
                connections.push([conn.sourceId, conn.targetId, _this.getNaivePathLength(rects[conn.sourceId], rects[conn.targetId]), conn]);
            });

            connections.sort(function (a, b) {
                return a[2] - b[2];
            });

            JsPlumbSmartlineManager.lastRequest = {
                sources: sources.boxes.map(function (item) {
                    return [item.cid, item.left, item.top, item.width, item.height];
                }),
                connections: connections.map(function (item) {return item.slice(0,2);})
            };

            _.each(connections, function (conn) {
                var finder = new Finder(graph);
                var sourceConnections = graph.findLeavingConnectionsByCid(conn[0], connectionDirection.TOP_TO_BOTTOM),
                    targetConnections = graph.findEnteringConnectionsByCid(conn[1], connectionDirection.TOP_TO_BOTTOM);
                for (var i = 0; i < sourceConnections.length; i++) {
                    finder.addFrom(sourceConnections[i]);
                }
                for (i = 0; i < targetConnections.length; i++) {
                    finder.addTo(targetConnections[i]);
                }

                var path = finder.find();
                if (!path) {
                    console.warn("Cannot find path");
                } else {
                    path.put();
                    cache.push({
                        connection: conn[3],
                        path: path
                    });
                }
            });
            for (var i = 0; i < cache.length; i++) {
                var item = cache[i];
                item.points = item.path.toPointsArray([]).reverse();
            }
            this.cache = cache;
        },

        getConnectionPath: function (connector) {
            this.calculate();
            for (var i = 0; i < this.cache.length; i++) {
                var item = this.cache[i];
                if (item.connection.connector === connector) {
                    return item.points;
                }
            }
            console.warn("Path not found");
            return [];
        }
    };

    return JsPlumbSmartlineManager;
});
