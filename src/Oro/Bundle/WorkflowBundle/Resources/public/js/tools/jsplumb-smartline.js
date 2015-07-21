define(function(require) {
    'use strict';

    var jsPlumb = require('jsplumb');
    var _ = require('underscore');
    var JsPlumbSmartlineManager = require('./jsplumb-smartline-manager');

    function ensureSmartLineManager(jsPlumbInstance) {
        if (!jsPlumbInstance.__smartLineManager) {
            jsPlumbInstance.__smartLineManager = new JsPlumbSmartlineManager(jsPlumbInstance);
        }
        return jsPlumbInstance.__smartLineManager;
    }

    function Smartline(params) {
        this.type = 'Smartline';
        this.idPrefix = 'smartline-connector-';
        params = params || {};
        params.stub = params.stub === null || params.stub === void 0 ? 30 : params.stub;
        var segments;
        var _super = jsPlumb.Connectors.AbstractConnector.apply(this, arguments);
        this.smartlineManager = ensureSmartLineManager(params._jsPlumb);
        var midpoint = params.midpoint === null || params.midpoint === void 0 ? 0.5 : params.midpoint;
        var alwaysRespectStubs = params.alwaysRespectStubs === true;
        var userSuppliedSegments = null;
        var lastx = null;
        var lasty = null;
        var lastOrientation;
        var cornerRadius = params.cornerRadius !== null && params.midpoint !== void 0 ? params.cornerRadius : 0;
        var showLoopback = params.showLoopback !== false;
        var sgn = function(n) {
            return n < 0 ? -1 : n === 0 ? 0 : 1;
        };
        /**
         * helper method to add a segment.
         */
        var addSegment = function(segments, x, y, paintInfo) {
            if (lastx === x && lasty === y) {
                return;
            }
            var lx = lastx === null ? paintInfo.sx : lastx;
            var ly = lasty === null ? paintInfo.sy : lasty;
            var o = lx === x ? 'v' : 'h';
            var sgnx = sgn(x - lx);
            var sgny = sgn(y - ly);

            lastx = x;
            lasty = y;
            segments.push([lx, ly, x, y, o, sgnx, sgny]);
        };
        var segLength = function(s) {
            return Math.sqrt(Math.pow(s[0] - s[2], 2) + Math.pow(s[1] - s[3], 2));
        };
        var _cloneArray = function(a) {
            var _a = [];
            _a.push.apply(_a, a);
            return _a;
        };
        var writeSegments = function(conn, segments, paintInfo) {
            var current = null;
            var next;
            for (var i = 0; i < segments.length - 1; i++) {
                current = current || _cloneArray(segments[i]);
                next = _cloneArray(segments[i + 1]);
                if (cornerRadius > 0 && current[4] !== next[4]) {
                    var radiusToUse = Math.min(cornerRadius, segLength(current), segLength(next));
                    // right angle. adjust current segment's end point, and next segment's start point.
                    current[2] -= current[5] * radiusToUse;
                    current[3] -= current[6] * radiusToUse;
                    next[0] += next[5] * radiusToUse;
                    next[1] += next[6] * radiusToUse;
                    var ac = (current[6] === next[5] && next[5] === 1) ||
                            ((current[6] === next[5] && next[5] === 0) && current[5] !== next[6]) ||
                            (current[6] === next[5] && next[5] === -1);
                    var sgny = next[1] > current[3] ? 1 : -1;
                    var sgnx = next[0] > current[2] ? 1 : -1;
                    var sgnEqual = sgny === sgnx;
                    var cx = (sgnEqual && ac || (!sgnEqual && !ac)) ? next[0] : current[2];
                    var cy = (sgnEqual && ac || (!sgnEqual && !ac)) ? current[3] : next[1];

                    _super.addSegment(conn, 'Straight', {
                        x1: current[0], y1: current[1], x2: current[2], y2: current[3]
                    });

                    _super.addSegment(conn, 'Arc', {
                        r: radiusToUse,
                        x1: current[2],
                        y1: current[3],
                        x2: next[0],
                        y2: next[1],
                        cx: cx,
                        cy: cy,
                        ac: ac
                    });
                } else {
                    // dx + dy are used to adjust for line width.
                    var dx = (current[2] === current[0]) ? 0 :
                        (current[2] > current[0]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2);
                    var dy = (current[3] === current[1]) ? 0 :
                        (current[3] > current[1]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2);
                    _super.addSegment(conn, 'Straight', {
                        x1: current[0] - dx, y1: current[1] - dy, x2: current[2] + dx, y2: current[3] + dy
                    });
                }
                current = next;
            }
            if (next) {
                // last segment
                _super.addSegment(conn, 'Straight', {
                    x1: next[0], y1: next[1], x2: next[2], y2: next[3]
                });
            }
        };

        this.setSegments = function(s) {
            userSuppliedSegments = s;
        };

        this.getSegs = function() {
            return segments;
        };

        this.getAbsSegments = function(conn) {
            var i;
            var j;
            var cur;
            var rect1 = conn.endpoints[0].canvas.getBoundingClientRect();
            var rect2 = conn.endpoints[1].canvas.getBoundingClientRect();
            var x = Math.min((rect1.left + rect1.right), (rect2.left + rect2.right)) / 2;
            var y = Math.min((rect1.top + rect1.bottom), (rect2.top + rect2.bottom)) / 2;
            var result = [];
            for (i = 0; i < segments.length; i++) {
                cur = [segments[i][0] + x, segments[i][1] + y, segments[i][2] + x, segments[i][3] + y, segments[i][4]];
                if (i > 0) {
                    j = result.length - 1;
                    if (cur[4] === 'v' && result[j][4] === 'v') {
                        if (cur[1] === result[j][3]) {
                            result[j][3] = cur[3];
                            continue;
                        } else if (cur[3] === result[j][1]) {
                            result[j][1] = cur[1];
                            continue;
                        }
                    } else if (cur[4] === 'h' && result[j][4] === 'h') {
                        if (cur[0] === result[j][2]) {
                            result[j][2] = cur[2];
                            continue;
                        } else if (cur[2] === result[j][0]) {
                            result[j][0] = cur[0];
                            continue;
                        }
                    }
                }
                result.push(cur);
            }
            return result;
        };

        this.isEditable = function() {
            return true;
        };

        /*
         Function: getOriginalSegments
         Gets the segments before the addition of rounded corners. This is used by the flowchart
         connector editor, since it only wants to concern itself with the original segments.
         */
        this.getOriginalSegments = function() {
            return userSuppliedSegments || segments;
        };

        function getAdjustment(el, point) {
            var style = window.getComputedStyle(el),
                borderRadius = 0,
                dx;
            if (style.borderRadius) {
                borderRadius = parseInt(style.borderRadius) || 0;
            }
            borderRadius = Math.min(borderRadius, el.offsetWidth / 2, el.offsetHeight / 2);

            var realX = point.x - el.offsetLeft;
            if (realX < 1 || realX > el.offsetWidth - 1) {
                return 0;
            }
            if (realX < borderRadius) {
                dx = borderRadius - realX;
                return Math.sqrt(borderRadius * borderRadius - dx * dx) - 1;
            } else if (realX > el.offsetWidth - borderRadius) {
                dx = realX - (el.offsetWidth - borderRadius);
                return Math.sqrt(borderRadius * borderRadius - dx * dx) - 1;
            }

            return el.offsetHeight / 2 - 1;
        }

        this._compute = function(paintInfo, params) {
            // compute the rest of the line
            var points = this.smartlineManager.getConnectionPath(this, paintInfo);
            if (points.length == 0) {
                // leave everything as is
                return;
            }

            var sourcePoint = points.shift().clone(),
                targetPoint = points.pop().clone(),
                correction,
                ENDPOINT_SPACE_TO_LINE = 4;

            // adjust source anf target points
            sourcePoint.y += getAdjustment(params.sourceEndpoint.element, sourcePoint);
            targetPoint.y -= getAdjustment(params.targetEndpoint.element, targetPoint);

            // find required correction
            correction = {
                x: Math.min(sourcePoint.x, targetPoint.x),
                y: Math.min(sourcePoint.y, targetPoint.y)
            };

            // that will be starting point of line
            paintInfo.sx = sourcePoint.x - correction.x;
            paintInfo.sy += ENDPOINT_SPACE_TO_LINE + 1;

            // set valid archors
            var oldAnchorX = params.sourceEndpoint.anchor.x,
                oldAnchorY = params.sourceEndpoint.anchor.y;
            params.sourceEndpoint.anchor.x = (sourcePoint.x - params.sourceEndpoint.element.offsetLeft)/ params.sourceEndpoint.element.offsetWidth;
            params.sourceEndpoint.anchor.y = (sourcePoint.y - params.sourceEndpoint.element.offsetTop)/ params.sourceEndpoint.element.offsetHeight;
            params.targetEndpoint.anchor.x = (targetPoint.x - params.targetEndpoint.element.offsetLeft)/ params.targetEndpoint.element.offsetWidth;
            params.targetEndpoint.anchor.y = (targetPoint.y - params.targetEndpoint.element.offsetTop)/ params.targetEndpoint.element.offsetHeight;

            if (oldAnchorX !== params.sourceEndpoint.anchor.x) {
                paintInfo.points[0] += (params.sourceEndpoint.anchor.x - oldAnchorX) * params.sourceEndpoint.element.offsetWidth;
            }
            if (oldAnchorY !== params.sourceEndpoint.anchor.y) {
                paintInfo.points[1] += (params.sourceEndpoint.anchor.y - oldAnchorY) * params.sourceEndpoint.element.offsetHeight;
            }

            // build segments
            lastx = null;
            lasty = null;
            lastOrientation = null;
            segments = [];

            if (points.length) {
                for (var i = 0; i < points.length; i++) {
                    addSegment(segments, points[i].x - correction.x, points[i].y - correction.y, paintInfo);
                }
            } else {
                addSegment(segments, sourcePoint.x - correction.x, sourcePoint.y - correction.y, paintInfo);
            }

            // end stub to end
            addSegment(segments, targetPoint.x - correction.x, targetPoint.y - correction.y - ENDPOINT_SPACE_TO_LINE, paintInfo);

            writeSegments(this, segments, paintInfo);
        };

        this.getPath = function() {
            var _last = null;
            var _lastAxis = null;
            var s = [];
            var segs = userSuppliedSegments || segments;
            var seg;
            var axis;
            var axisIndex;
            for (var i = 0; i < segs.length; i++) {
                seg = segs[i];
                axis = seg[4];
                axisIndex = (axis === 'v' ? 3 : 2);
                if (_last !== null && _lastAxis === axis) {
                    _last[axisIndex] = seg[axisIndex];
                } else {
                    if (seg[0] !== seg[2] || seg[1] !== seg[3]) {
                        s.push({
                            start: [seg[0], seg[1]],
                            end: [seg[2], seg[3]]
                        });
                        _last = seg;
                        _lastAxis = seg[4];
                    }
                }
            }
            return s;
        };

        this.setPath = function(path) {
            userSuppliedSegments = [];
            for (var i = 0; i < path.length; i++) {
                var lx = path[i].start[0];
                var ly = path[i].start[1];
                var x = path[i].end[0];
                var y = path[i].end[1];
                var o = lx === x ? 'v' : 'h';
                var sgnx = sgn(x - lx);
                var sgny = sgn(y - ly);

                userSuppliedSegments.push([lx, ly, x, y, o, sgnx, sgny]);
            }
        };
    }

    function juExtend(child, parent, _protoFn) {
        var i;
        parent = Object.prototype.toString.call(parent) === '[object Array]' ? parent : [parent];

        for (i = 0; i < parent.length; i++) {
            for (var j in parent[i].prototype) {
                if (parent[i].prototype.hasOwnProperty(j)) {
                    child.prototype[j] = parent[i].prototype[j];
                }
            }
        }

        var _makeFn = function(name, protoFn) {
            return function() {
                for (i = 0; i < parent.length; i++) {
                    if (parent[i].prototype[name]) {
                        parent[i].prototype[name].apply(this, arguments);
                    }
                }
                return protoFn.apply(this, arguments);
            };
        };

        var _oneSet = function(fns) {
            for (var k in fns) {
                if (fns.hasOwnProperty(k)) {
                    child.prototype[k] = _makeFn(k, fns[k]);
                }
            }
        };

        if (arguments.length > 2) {
            for (i = 2; i < arguments.length; i++) {
                _oneSet(arguments[i]);
            }
        }

        return child;
    }

    juExtend(Smartline, jsPlumb.Connectors.AbstractConnector);
    jsPlumb.registerConnectorType(Smartline, 'Smartline');
    _.each(jsPlumb.getRenderModes(), function(renderer) {
        jsPlumb.Connectors[renderer].Smartline = function() {
            Smartline.apply(this, arguments);
            jsPlumb.ConnectorRenderers[renderer].apply(this, arguments);
        };
        juExtend(jsPlumb.Connectors[renderer].Smartline, [Smartline, jsPlumb.ConnectorRenderers[renderer]]);
    });

    return Smartline;
});
