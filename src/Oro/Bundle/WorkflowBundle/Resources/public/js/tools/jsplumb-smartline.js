/* jslint ignore:start */
define(['jsplumb', 'underscore', './jsplumb-smartline-manager'], function (_jp, _, JsPlumbSmartlineManager) {
    "use strict";
    var Smartline = function (params) {
        this.type = "Smartline";
        params = params || {};
        params.stub = params.stub == null ? 30 : params.stub;
        var segments,
            _super = _jp.Connectors.AbstractConnector.apply(this, arguments),
            midpoint = params.midpoint == null ? 0.5 : params.midpoint,
            alwaysRespectStubs = params.alwaysRespectStubs === true,
            userSuppliedSegments = null,
            lastx = null, lasty = null, lastOrientation,
            cornerRadius = params.cornerRadius != null ? params.cornerRadius : 0,
            showLoopback = params.showLoopback !== false,
            sgn = function (n) {
                return n < 0 ? -1 : n === 0 ? 0 : 1;
            },
            /**
             * helper method to add a segment.
             */
            addSegment = function (segments, x, y, paintInfo) {
                if (lastx == x && lasty == y) return;
                var lx = lastx == null ? paintInfo.sx : lastx,
                    ly = lasty == null ? paintInfo.sy : lasty,
                    o = lx == x ? "v" : "h",
                    sgnx = sgn(x - lx),
                    sgny = sgn(y - ly);

                lastx = x;
                lasty = y;
                segments.push([lx, ly, x, y, o, sgnx, sgny]);
            },
            segLength = function (s) {
                return Math.sqrt(Math.pow(s[0] - s[2], 2) + Math.pow(s[1] - s[3], 2));
            },
            _cloneArray = function (a) {
                var _a = [];
                _a.push.apply(_a, a);
                return _a;
            },
            writeSegments = function (conn, segments, paintInfo) {
                var current = null, next;
                for (var i = 0; i < segments.length - 1; i++) {

                    current = current || _cloneArray(segments[i]);
                    next = _cloneArray(segments[i + 1]);
                    if (cornerRadius > 0 && current[4] != next[4]) {
                        var radiusToUse = Math.min(cornerRadius, segLength(current), segLength(next));
                        // right angle. adjust current segment's end point, and next segment's start point.
                        current[2] -= current[5] * radiusToUse;
                        current[3] -= current[6] * radiusToUse;
                        next[0] += next[5] * radiusToUse;
                        next[1] += next[6] * radiusToUse;
                        var ac = (current[6] == next[5] && next[5] == 1) ||
                                ((current[6] == next[5] && next[5] === 0) && current[5] != next[6]) ||
                                (current[6] == next[5] && next[5] == -1),
                            sgny = next[1] > current[3] ? 1 : -1,
                            sgnx = next[0] > current[2] ? 1 : -1,
                            sgnEqual = sgny == sgnx,
                            cx = (sgnEqual && ac || (!sgnEqual && !ac)) ? next[0] : current[2],
                            cy = (sgnEqual && ac || (!sgnEqual && !ac)) ? current[3] : next[1];

                        _super.addSegment(conn, "Straight", {
                            x1: current[0], y1: current[1], x2: current[2], y2: current[3]
                        });

                        _super.addSegment(conn, "Arc", {
                            r: radiusToUse,
                            x1: current[2],
                            y1: current[3],
                            x2: next[0],
                            y2: next[1],
                            cx: cx,
                            cy: cy,
                            ac: ac
                        });
                    }
                    else {
                        // dx + dy are used to adjust for line width.
                        var dx = (current[2] == current[0]) ? 0 : (current[2] > current[0]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2),
                            dy = (current[3] == current[1]) ? 0 : (current[3] > current[1]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2);
                        _super.addSegment(conn, "Straight", {
                            x1: current[0] - dx, y1: current[1] - dy, x2: current[2] + dx, y2: current[3] + dy
                        });
                    }
                    current = next;
                }
                if (next != null) {
                    // last segment
                    _super.addSegment(conn, "Straight", {
                        x1: next[0], y1: next[1], x2: next[2], y2: next[3]
                    });
                }
            };

        this.setSegments = function (s) {
            userSuppliedSegments = s;
        };

        this.getSegs = function () {
            return segments;
        };

        this.getAbsSegments = function (conn) {
            var i,
                j,
                cur,
                rect1 = conn.endpoints[0].canvas.getBoundingClientRect(),
                rect2 = conn.endpoints[1].canvas.getBoundingClientRect(),
                x = Math.min((rect1.left + rect1.right), (rect2.left + rect2.right)) / 2,
                y = Math.min((rect1.top + rect1.bottom),(rect2.top + rect2.bottom)) / 2,
                result = [];
            for (i = 0; i < segments.length; i++) {
                cur = [segments[i][0] + x, segments[i][1] + y, segments[i][2] + x, segments[i][3] + y, segments[i][4]];
                if(i > 0) {
                    j = result.length - 1;
                    if (cur[4] == 'v' && result[j][4] == 'v') {
                        if (cur[1] == result[j][3]) {
                            result[j][3] = cur[3];
                            continue;
                        } else if (cur[3] == result[j][1]) {
                            result[j][1] = cur[1];
                            continue;
                        }
                    } else if (cur[4] == 'h' && result[j][4] == 'h') {
                        if (cur[0] == result[j][2]) {
                            result[j][2] = cur[2];
                            continue;
                        } else if (cur[2] == result[j][0]) {
                            result[j][0] = cur[0];
                            continue;
                        }
                    }
                }
                result.push(cur);
            }
            return result;
        };

        this.isEditable = function () {
            return true;
        };

        /*
         Function: getOriginalSegments
         Gets the segments before the addition of rounded corners. This is used by the flowchart
         connector editor, since it only wants to concern itself with the original segments.
         */
        this.getOriginalSegments = function () {
            return userSuppliedSegments || segments;
        };

        this.ensureSmartlineManager = function () {
            if (!this._jsPlumb.smartlineManager) {
                this._jsPlumb.smartlineManager = new JsPlumbSmartlineManager(this._jsPlumb);
            }
        };

        this._compute = function (paintInfo, params) {

            if (params.clearEdits)
                userSuppliedSegments = null;

            if (userSuppliedSegments != null) {
                writeSegments(this, userSuppliedSegments, paintInfo);
                return;
            }

            lastx = null;
            lasty = null;
            lastOrientation = null;

            segments = [];

            // compute the rest of the line
            this.ensureSmartlineManager();
            var points = this._jsPlumb.smartlineManager.getConnectionPath(this);
            if (points.length == 0) {
                // leave everything as is
                return;
            }
            // set valid archors
            var sourceRect = params.sourceEndpoint.element.getBoundingClientRect(),
                targetRect = params.targetEndpoint.element.getBoundingClientRect(),
                sourcePoint = points.shift(),
                targetPoint = points.pop(),
                connectionWidth = 16,
                correction;
            params.sourceEndpoint.anchor.x = (sourcePoint.x - sourceRect.left)/ sourceRect.width;
            params.sourceEndpoint.anchor.y = (sourcePoint.y - 16 - sourceRect.top)/ sourceRect.height;
            params.targetEndpoint.anchor.x = (targetPoint.x - targetRect.left)/ targetRect.width;
            params.targetEndpoint.anchor.y = (targetPoint.y + 16 - targetRect.top)/ targetRect.height;
            correction = {
                x: Math.min(sourcePoint.x, targetPoint.x),
                y: Math.min(sourcePoint.y - 16, targetPoint.y + 16)
            }

            if (points.length) {
                for (var i = 0; i < points.length; i++) {
                    addSegment(segments, points[i].x - correction.x, points[i].y - correction.y, paintInfo);
                }
            } else {
                addSegment(segments, sourcePoint.x - correction.x, sourcePoint.y - correction.y, paintInfo);
            }

            // addSegment(segments, points[i].x - targetPoint.x, points[i].y - targetPoint.y, paintInfo);

            // end stub to end
            addSegment(segments, targetPoint.x - correction.x, targetPoint.y + 16 - correction.y, paintInfo);

            writeSegments(this, segments, paintInfo);
        };

        this.getPath = function () {
            var _last = null, _lastAxis = null, s = [], segs = userSuppliedSegments || segments;
            for (var i = 0; i < segs.length; i++) {
                var seg = segs[i], axis = seg[4], axisIndex = (axis == "v" ? 3 : 2);
                if (_last != null && _lastAxis === axis) {
                    _last[axisIndex] = seg[axisIndex];
                }
                else {
                    if (seg[0] != seg[2] || seg[1] != seg[3]) {
                        s.push({
                            start: [ seg[0], seg[1] ],
                            end: [ seg[2], seg[3] ]
                        });
                        _last = seg;
                        _lastAxis = seg[4];
                    }
                }
            }
            return s;
        };

        this.setPath = function (path) {
            userSuppliedSegments = [];
            for (var i = 0; i < path.length; i++) {
                var lx = path[i].start[0],
                    ly = path[i].start[1],
                    x = path[i].end[0],
                    y = path[i].end[1],
                    o = lx == x ? "v" : "h",
                    sgnx = sgn(x - lx),
                    sgny = sgn(y - ly);

                userSuppliedSegments.push([lx, ly, x, y, o, sgnx, sgny]);
            }
        };
    };


    function juExtend(child, parent, _protoFn) {
        var i;
        parent = Object.prototype.toString.call(parent) === "[object Array]" ? parent : [ parent ];

        for (i = 0; i < parent.length; i++) {
            for (var j in parent[i].prototype) {
                if (parent[i].prototype.hasOwnProperty(j)) {
                    child.prototype[j] = parent[i].prototype[j];
                }
            }
        }

        var _makeFn = function (name, protoFn) {
            return function () {
                for (i = 0; i < parent.length; i++) {
                    if (parent[i].prototype[name])
                        parent[i].prototype[name].apply(this, arguments);
                }
                return protoFn.apply(this, arguments);
            };
        };

        var _oneSet = function (fns) {
            for (var k in fns) {
                child.prototype[k] = _makeFn(k, fns[k]);
            }
        };

        if (arguments.length > 2) {
            for (i = 2; i < arguments.length; i++)
                _oneSet(arguments[i]);
        }

        return child;
    }

    juExtend(Smartline, jsPlumb.Connectors.AbstractConnector);
    jsPlumb.registerConnectorType(Smartline, 'Smartline');
    _.each(jsPlumb.getRenderModes(), function (renderer) {
        jsPlumb.Connectors[renderer]['Smartline'] = function () {
            Smartline.apply(this, arguments);
            jsPlumb.ConnectorRenderers[renderer].apply(this, arguments);
        };
        juExtend(jsPlumb.Connectors[renderer]['Smartline'], [ Smartline, jsPlumb.ConnectorRenderers[renderer]]);
    });

    return Smartline;

})
/* jslint ignore:end */
