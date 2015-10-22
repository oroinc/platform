define(function(require) {
    'use strict';

    var jsPlumb = require('jsplumb');
    var $ = require('jquery');
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

        function getBorderRadius(el, directionY, directionX) {
            var borderRadius = 0;
            var maxPossibleBorderRadius = Math.min(el.offsetWidth / 2, el.offsetHeight / 2);
            var propName = ['border', directionY, directionX, 'radius'].join('-');
            var styles = window.getComputedStyle(el);
            if (styles[propName] && styles[propName] !== 'none') {
                borderRadius = Math.min(parseFloat(styles[propName]) || 0, maxPossibleBorderRadius);
            }
            return borderRadius;
        }

        function getAdjustment(el, point, direction) {
            var realX = point.x - el.offsetLeft;
            if (realX < 1 || realX > el.offsetWidth - 1) {
                return 0;
            }
            var dx;
            var borderRadius;
            if (realX < el.offsetWidth / 2) {
                borderRadius = getBorderRadius(el, direction, 'left');
                dx = borderRadius - realX;
                if (dx > 0) {
                    return Math.sqrt(borderRadius * borderRadius - dx * dx) - 1;
                }
            } else {
                borderRadius = getBorderRadius(el, direction, 'right');
                dx = realX - (el.offsetWidth - borderRadius);
                if (dx > 0) {
                    return Math.sqrt(borderRadius * borderRadius - dx * dx) - 1;
                }
            }

            return el.offsetHeight / 2 - 1;
        }

        function getSourceElement(elem, pos) {
            return _.find($(elem).find('.jsplumb-source').toArray(), function(source) {
                var offsetLeft = elem.offsetLeft + source.offsetLeft;
                var offsetTop = elem.offsetTop + source.offsetTop;
                return pos[0] >= offsetLeft && pos[0] <= (offsetLeft + source.offsetWidth) &&
                    pos[1] >= offsetTop && pos[1] <= (offsetTop + source.offsetHeight);
            });
        }

        function adjustSourcePosition(paintInfo, params) {
            var elem = params.sourceEndpoint.element;
            var source = getSourceElement(elem, params.sourcePos);
            var sourceStyle = window.getComputedStyle(source);
            if (sourceStyle.visibility === 'hidden' || sourceStyle.display === 'none') {
                dockSourcePositionToEdge(elem, paintInfo, params);
            } else if (source) {
                paintInfo.points[0] +=
                    (elem.offsetLeft + source.offsetLeft + source.offsetWidth / 2) - params.sourcePos[0];
                paintInfo.points[1] +=
                    (elem.offsetTop + source.offsetTop + source.offsetHeight / 2) - params.sourcePos[1];
            }
        }

        function dockSourcePositionToEdge(elem, paintInfo, params) {
            var centerX; // center of curve of rounded corner
            var centerY; // center of curve of rounded corner
            var ratio;
            var directionX = elem.offsetLeft + elem.offsetWidth / 2 - params.sourcePos[0] >= 0 ? 'left' : 'right';
            var directionY = elem.offsetTop + elem.offsetHeight / 2 - params.sourcePos[1] >= 0 ? 'top' : 'bottom';
            var radius = getBorderRadius(elem, directionY, directionX);
            if (directionX === 'left') {
                centerX = elem.offsetLeft + radius;
            } else {
                centerX = elem.offsetLeft + elem.offsetWidth - radius;
            }
            if (directionY === 'top') {
                centerY = elem.offsetTop + radius;
            } else {
                centerY = elem.offsetTop + elem.offsetHeight - radius;
            }
            var dx = params.sourcePos[0] - centerX;
            var dy = params.sourcePos[1] - centerY;
            if (directionX === 'left' && dx >= 0 || directionX === 'right' && dx <= 0) { // dock to horizontal side
                if (directionY === 'top') {
                    paintInfo.points[1] += elem.offsetTop - params.sourcePos[1];
                } else {
                    paintInfo.points[1] += elem.offsetTop + elem.offsetHeight - params.sourcePos[1];
                }
            } else if (directionY === 'top' && dy >= 0 || directionY === 'bottom' && dy <= 0) { // dock to vertical side
                if (directionX === 'left') {
                    paintInfo.points[0] += elem.offsetLeft - params.sourcePos[0];
                } else {
                    paintInfo.points[0] += elem.offsetLeft + elem.offsetWidth - params.sourcePos[0];
                }
            } else { // dock to rounded corner
                ratio = (radius - 1.5) / Math.sqrt(dx * dx + dy * dy);
                paintInfo.points[0] += centerX + dx * ratio - params.sourcePos[0];
                paintInfo.points[1] += centerY + dy * ratio - params.sourcePos[1];
            }
        }

        this._compute = function(paintInfo, params) {
            if (params.sourceEndpoint.isTemporarySource || params.sourceEndpoint.getAttachedElements().length === 0 ||
                params.targetEndpoint.getAttachedElements().length === 0) {
                // in case this connection is new one or is moving to another target or source
                // use jsPlumb Flowchart connector behaviour
                adjustSourcePosition(paintInfo, params);
                return this._flowchartConnectorCompute.apply(this, arguments);
            }

            // compute the rest of the line
            var points = this.smartlineManager.getConnectionPath(this, paintInfo);
            if (points.length === 0) {
                // leave everything as is
                return;
            }

            var sourcePoint = points.shift().clone();
            var targetPoint = points.pop().clone();
            var correction;
            var ENDPOINT_SPACE_TO_LINE = 4;

            // adjust source and target points
            sourcePoint.y += getAdjustment(params.sourceEndpoint.element, sourcePoint, 'bottom');
            targetPoint.y -= getAdjustment(params.targetEndpoint.element, targetPoint, 'top');

            // find required correction
            correction = {
                x: Math.min(sourcePoint.x, targetPoint.x),
                y: Math.min(sourcePoint.y, targetPoint.y)
            };

            // that will be starting point of line
            paintInfo.sx = sourcePoint.x - correction.x;
            paintInfo.sy += ENDPOINT_SPACE_TO_LINE + 1;

            // set valid archors
            var oldAnchorX = params.sourceEndpoint.anchor.x;
            var oldAnchorY = params.sourceEndpoint.anchor.y;
            params.sourceEndpoint.anchor.x = (sourcePoint.x - params.sourceEndpoint.element.offsetLeft) /
                params.sourceEndpoint.element.offsetWidth;
            params.sourceEndpoint.anchor.y = (sourcePoint.y - params.sourceEndpoint.element.offsetTop) /
                params.sourceEndpoint.element.offsetHeight;
            params.targetEndpoint.anchor.x = (targetPoint.x - params.targetEndpoint.element.offsetLeft) /
                params.targetEndpoint.element.offsetWidth;
            params.targetEndpoint.anchor.y = (targetPoint.y - params.targetEndpoint.element.offsetTop) /
                params.targetEndpoint.element.offsetHeight;

            if (oldAnchorX !== params.sourceEndpoint.anchor.x) {
                paintInfo.points[0] += (params.sourceEndpoint.anchor.x - oldAnchorX) *
                    params.sourceEndpoint.element.offsetWidth;
            }
            if (oldAnchorY !== params.sourceEndpoint.anchor.y) {
                paintInfo.points[1] += (params.sourceEndpoint.anchor.y - oldAnchorY) *
                    params.sourceEndpoint.element.offsetHeight;
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
            addSegment(segments, targetPoint.x - correction.x, targetPoint.y - correction.y - ENDPOINT_SPACE_TO_LINE,
                paintInfo);

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

        this._flowchartConnectorCompute = function(paintInfo, params) {
            if (params.clearEdits) {
                userSuppliedSegments = null;
            }

            if (userSuppliedSegments !== null) {
                writeSegments(this, userSuppliedSegments, paintInfo);
                return;
            }

            segments = [];
            lastx = null;
            lasty = null;
            lastOrientation = null;

            var midx = paintInfo.startStubX + ((paintInfo.endStubX - paintInfo.startStubX) * midpoint);
            var midy = paintInfo.startStubY + ((paintInfo.endStubY - paintInfo.startStubY) * midpoint);
            var orientations = {x: [0, 1], y: [1, 0]};
            var commonStubCalculator = function() {
                    return [paintInfo.startStubX, paintInfo.startStubY, paintInfo.endStubX, paintInfo.endStubY];
                };
            var stubCalculators = {
                    perpendicular: commonStubCalculator,
                    orthogonal: commonStubCalculator,
                    opposite: function(axis) {
                        var pi = paintInfo;
                        var idx = axis === 'x' ? 0 : 1;
                        var areInProximity = {
                                'x': function() {
                                    return ((pi.so[idx] === 1 && (
                                        ((pi.startStubX > pi.endStubX) && (pi.tx > pi.startStubX)) ||
                                        ((pi.sx > pi.endStubX) && (pi.tx > pi.sx))))) ||

                                        ((pi.so[idx] === -1 && (
                                        ((pi.startStubX < pi.endStubX) && (pi.tx < pi.startStubX)) ||
                                        ((pi.sx < pi.endStubX) && (pi.tx < pi.sx)))));
                                },
                                'y': function() {
                                    return ((pi.so[idx] === 1 && (
                                        ((pi.startStubY > pi.endStubY) && (pi.ty > pi.startStubY)) ||
                                        ((pi.sy > pi.endStubY) && (pi.ty > pi.sy))))) ||

                                        ((pi.so[idx] === -1 && (
                                        ((pi.startStubY < pi.endStubY) && (pi.ty < pi.startStubY)) ||
                                        ((pi.sy < pi.endStubY) && (pi.ty < pi.sy)))));
                                }
                            };

                        if (!alwaysRespectStubs && areInProximity[axis]()) {
                            return {
                                'x': [
                                    (paintInfo.sx + paintInfo.tx) / 2,
                                    paintInfo.startStubY,
                                    (paintInfo.sx + paintInfo.tx) / 2,
                                    paintInfo.endStubY
                                ],
                                'y': [
                                    paintInfo.startStubX,
                                    (paintInfo.sy + paintInfo.ty) / 2,
                                    paintInfo.endStubX,
                                    (paintInfo.sy + paintInfo.ty) / 2
                                ]
                            }[axis];
                        } else {
                            return [paintInfo.startStubX, paintInfo.startStubY, paintInfo.endStubX, paintInfo.endStubY];
                        }
                    }
                };
            var lineCalculators = {
                    perpendicular: function(axis) {
                        var pi = paintInfo;
                        var sis = {
                                x: [
                                    [[1, 2, 3, 4], null, [2, 1, 4, 3]],
                                    null,
                                    [[4, 3, 2, 1], null, [3, 4, 1, 2]]
                                ],
                                y: [
                                    [[3, 2, 1, 4], null, [2, 3, 4, 1]],
                                    null,
                                    [[4, 1, 2, 3], null, [1, 4, 3, 2]]
                                ]
                            };
                        var stubs = {
                                x: [[pi.startStubX, pi.endStubX], null, [pi.endStubX, pi.startStubX]],
                                y: [[pi.startStubY, pi.endStubY], null, [pi.endStubY, pi.startStubY]]
                            };
                        var midLines = {
                                x: [[midx, pi.startStubY], [midx, pi.endStubY]],
                                y: [[pi.startStubX, midy], [pi.endStubX, midy]]
                            };
                        var linesToEnd = {
                                x: [[pi.endStubX, pi.startStubY]],
                                y: [[pi.startStubX, pi.endStubY]]
                            };
                        var startToEnd = {
                                x: [[pi.startStubX, pi.endStubY], [pi.endStubX, pi.endStubY]],
                                y: [[pi.endStubX, pi.startStubY], [pi.endStubX, pi.endStubY]]
                            };
                        var startToMidToEnd = {
                                x: [[pi.startStubX, midy], [pi.endStubX, midy], [pi.endStubX, pi.endStubY]],
                                y: [[midx, pi.startStubY], [midx, pi.endStubY], [pi.endStubX, pi.endStubY]]
                            };
                        var otherStubs = {
                                x: [pi.startStubY, pi.endStubY],
                                y: [pi.startStubX, pi.endStubX]
                            };
                        var soIdx = orientations[axis][0];
                        var toIdx = orientations[axis][1];
                        var _so = pi.so[soIdx] + 1;
                        var _to = pi.to[toIdx] + 1;
                        var otherFlipped = (pi.to[toIdx] === -1 && (otherStubs[axis][1] < otherStubs[axis][0])) ||
                                (pi.to[toIdx] === 1 && (otherStubs[axis][1] > otherStubs[axis][0]));
                        var stub1 = stubs[axis][_so][0];
                        var stub2 = stubs[axis][_so][1];
                        var segmentIndexes = sis[axis][_so][_to];

                        if (pi.segment === segmentIndexes[3] || (pi.segment === segmentIndexes[2] && otherFlipped)) {
                            return midLines[axis];
                        } else if (pi.segment === segmentIndexes[2] && stub2 < stub1) {
                            return linesToEnd[axis];
                        } else if ((pi.segment === segmentIndexes[2] && stub2 >= stub1) ||
                            (pi.segment === segmentIndexes[1] && !otherFlipped)) {
                            return startToMidToEnd[axis];
                        } else if (pi.segment === segmentIndexes[0] ||
                            (pi.segment === segmentIndexes[1] && otherFlipped)) {
                            return startToEnd[axis];
                        }
                    },
                    orthogonal: function(axis, startStub, otherStartStub, endStub, otherEndStub) {
                        var pi = paintInfo;
                        var extent = {
                                'x': pi.so[0] === -1 ? Math.min(startStub, endStub) : Math.max(startStub, endStub),
                                'y': pi.so[1] === -1 ? Math.min(startStub, endStub) : Math.max(startStub, endStub)
                            }[axis];

                        return {
                            'x': [
                                [extent, otherStartStub],
                                [extent, otherEndStub],
                                [endStub, otherEndStub]
                            ],
                            'y': [
                                [otherStartStub, extent],
                                [otherEndStub, extent],
                                [otherEndStub, endStub]
                            ]
                        }[axis];
                    },
                    opposite: function(axis, ss, oss, es) {
                        var pi = paintInfo;
                        var otherAxis = {'x': 'y', 'y': 'x'}[axis];
                        var dim = {'x': 'height', 'y': 'width'}[axis];
                        var comparator = pi['is' + axis.toUpperCase() + 'GreaterThanStubTimes2'];

                        if (params.sourceEndpoint.elementId === params.targetEndpoint.elementId) {
                            var _val = oss + ((1 - params.sourceEndpoint.anchor[otherAxis]) * params.sourceInfo[dim]) +
                                _super.maxStub;
                            return {
                                'x': [
                                    [ss, _val],
                                    [es, _val]
                                ],
                                'y': [
                                    [_val, ss],
                                    [_val, es]
                                ]
                            }[axis];

                        } else if (!comparator || (pi.so[idx] === 1 && ss > es) || (pi.so[idx] === -1 && ss < es)) {
                            return {
                                'x': [
                                    [ss, midy],
                                    [es, midy]
                                ],
                                'y': [
                                    [midx, ss],
                                    [midx, es]
                                ]
                            }[axis];
                        } else if ((pi.so[idx] === 1 && ss < es) || (pi.so[idx] === -1 && ss > es)) {
                            return {
                                'x': [
                                    [midx, pi.sy],
                                    [midx, pi.ty]
                                ],
                                'y': [
                                    [pi.sx, midy],
                                    [pi.tx, midy]
                                ]
                            }[axis];
                        }
                    }
                };

            var stubs = stubCalculators[paintInfo.anchorOrientation](paintInfo.sourceAxis);
            var idx = paintInfo.sourceAxis === 'x' ? 0 : 1;
            var oidx = paintInfo.sourceAxis === 'x' ? 1 : 0;
            var ss = stubs[idx];
            var oss = stubs[oidx];
            var es = stubs[idx + 2];
            var oes = stubs[oidx + 2];

            // add the start stub segment.
            addSegment(segments, stubs[0], stubs[1], paintInfo);

            // compute the rest of the line
            var p = lineCalculators[paintInfo.anchorOrientation](paintInfo.sourceAxis, ss, oss, es, oes);
            if (p) {
                for (var i = 0; i < p.length; i++) {
                    addSegment(segments, p[i][0], p[i][1], paintInfo);
                }
            }

            // line to end stub
            addSegment(segments, stubs[2], stubs[3], paintInfo);

            // end stub to end
            addSegment(segments, paintInfo.tx, paintInfo.ty, paintInfo);

            writeSegments(this, segments, paintInfo);
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
