define(['underscore'], function (_) {
    "use strict";

    function getEdge (params) {
        var edge = '';
        if(params[0] === 1) {
            edge = 'right';
        } else if(params[0] === 0) {
            edge = 'left';
        } else if(params[1] === 0) {
            edge = 'top';
        } else if(params[1] === 1) {
            edge = 'bottom';
        }
        return edge;
    }

    function between(a, b, c) {
        return Math.min(a, c) < b && Math.max(a, c) > b;
    }

    function overlap(a,b) {
        if(a[0] == a[2] && b[0] == b[2] && a[0] == b[0]) {
            return between(a[1], b[1], a[3]) || between(a[1], b[3], a[3]);
        } else if(a[1] == a[3] && b[1] == b[3] && a[1] == b[3]) {
            return between(a[0], b[0], a[2]) || between(a[0], b[2], a[2]);
        }
        return false;
    }

    function intersect(a,b) {
        var v1, v2, v3, v4;
        v1 = (b[2] - b[0]) * (a[1] - b[1]) - (b[3] - b[1]) * (a[0] - b[0]);
        v2 = (b[2] - b[0]) * (a[3] - b[1]) - (b[3] - b[1]) * (a[2] - b[0]);
        v3 = (a[2] - a[0]) * (b[1] - a[1]) - (a[3] - a[1]) * (b[0] - a[0]);
        v4 = (a[2] - a[0]) * (b[3] - a[1]) - (a[3] - a[1]) * (b[2] - a[0]);
        return (v1 * v2 < 0) && (v3 * v4 < 0);
    }

    var positions = [0.5, 0.8, 0.2, 0.65, 0.35, 0.6, 0.3, 0.7, 0.4],
        mids = {
            'top': [0.5, 0, 0, -1],
            'bottom': [0.5, 1, 0, 1],
            'left': [0, 0.5, -1, 0],
            'right': [1, 0.5, 1, 0]
        },
        JsPlumbManager = function (jsplumb) {
            this.jp = jsplumb;
            this.loopback = {};
            this.loopbackAnchorPreset = [
                [[1, 0.3, 1, 0],[0.8, 0, 0, -1]],
                [[0.2, 1, 0, 1],[0, 0.7, -1, 0]],
                [[1, 0.5, 1, 0],[0.5, 0, 0, -1]],
                [[0.5, 1, 0, 1],[0, 0.5, -1, 0]]
            ];

        };

    _.extend(JsPlumbManager.prototype, {

        getLoopbackAnchors: function (elId) {
            var preset, ind, presets = this.loopbackAnchorPreset;
            if (!(elId in this.loopback)) {
                this.loopback[elId] = [];
            }
            preset = presets[this.loopback[elId].length % presets.length];
            this.loopback[elId].push(preset);
            return preset;
        },

        getAnchors: function (sEl, tEl) {
            if (sEl === tEl) {
                return this.getLoopbackAnchors(sEl.id);
            }
            var sp = sEl.getBoundingClientRect(),
                tp = tEl.getBoundingClientRect(),
                sa, ta;
            if (sp.right < (tp.left + tp.right) / 2) {
                sa = mids.right;
                if (sp.bottom > tp.top) {
                    ta = mids.left;
                } else {
                    ta = mids.top;
                }
            } else {
                sa = mids.bottom;
                ta = mids.top;
            }

            return [sa, ta];
        },

        getIntersections: function(conn) {
            var that = this,
                segs = conn.connector.getAbsSegments(conn),
                collection = [];
            _.each(that.jp.getConnections(), function (c) {
                if(c !== conn && _.isArray(c.endpoints) && c.endpoints.length === 2) {
                    _.each(c.connector.getAbsSegments(c), function (s1) {
                        _.each(segs, function (s2) {
                            if (intersect(s1, s2) || overlap(s1, s2)) {
                                collection.push(c.overlayView.model.get('label'));
                            }
                        });
                    });
                }
            });
            return collection;
        },

        recalculateConnections: function () {
            function process (ep, anchor) {
                var i,
                    edge = getEdge(anchor),
                    key = ep.element.id + '_' + edge;
                if(key in that.anchors === false) {
                    that.anchors[key] = [];
                }
                i = that.anchors[key].length % positions.length;
                if( edge === 'top' || edge === 'bottom') {
                    anchor[0] = positions[i];
                } else {
                    anchor[1] = positions[i];
                }

                that.anchors[key].push(anchor);
                ep.setAnchor(anchor);
            }
            var that = this;
            that.loopback = {};
            that.anchors = {};
             _.each(that.jp.getConnections(), function (conn) {
                var anchors, se, te;
                if(_.isArray(conn.endpoints) && conn.endpoints.length === 2) {
                    se = conn.endpoints[0];
                    te = conn.endpoints[1];

                    anchors = that.getAnchors(se.element, te.element);
                    process(se, anchors[0]);
                    process(te, anchors[1]);
                }
            });
            console.groupCollapsed('Intersections');
            _.each(that.jp.getConnections(), function (conn) {
                var is = that.getIntersections(conn),
                    msg = 'Connection "' + conn.overlayView.model.get('label') + '" has ' + (is.length ? is.length : 'not.');
                if(is.length) {
                    msg += ' : ' + is.join(', ');
                }
                console.log(msg);
            });
            console.groupEnd();
        }
    });

    return JsPlumbManager;
});
