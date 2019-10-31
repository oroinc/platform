define(function(require) {
    'use strict';

    const jsPlumb = require('jsplumb');
    const $ = require('jquery');
    const _ = require('underscore');
    const JsPlumbSmartlineManager = require('./jsplumb-smartline-manager');

    function ensureSmartLineManager(jsPlumbInstance) {
        if (!jsPlumbInstance.__smartLineManager) {
            jsPlumbInstance.__smartLineManager = new JsPlumbSmartlineManager(jsPlumbInstance);
        }
        return jsPlumbInstance.__smartLineManager;
    }

    function Smartline(params, ...args) {
        this.type = 'Smartline';
        this.idPrefix = 'smartline-connector-';
        params = params || {};
        params.stub = params.stub === null || params.stub === void 0 ? 30 : params.stub;
        let segments;
        const _super = jsPlumb.Connectors.AbstractConnector.call(this, params, ...args);
        this.smartlineManager = ensureSmartLineManager(params._jsPlumb);
        const midpoint = params.midpoint === null || params.midpoint === void 0 ? 0.5 : params.midpoint;
        const alwaysRespectStubs = params.alwaysRespectStubs === true;
        let userSuppliedSegments = null;
        let lastx = null;
        let lasty = null;
        const cornerRadius = params.cornerRadius !== null && params.midpoint !== void 0 ? params.cornerRadius : 0;
        const sgn = function(n) {
            return n < 0 ? -1 : n === 0 ? 0 : 1;
        };
        /**
         * helper method to add a segment.
         */
        const addSegment = function(segments, x, y, paintInfo) {
            if (lastx === x && lasty === y) {
                return;
            }
            const lx = lastx === null ? paintInfo.sx : lastx;
            const ly = lasty === null ? paintInfo.sy : lasty;
            const o = lx === x ? 'v' : 'h';
            const sgnx = sgn(x - lx);
            const sgny = sgn(y - ly);

            lastx = x;
            lasty = y;
            segments.push([lx, ly, x, y, o, sgnx, sgny]);
        };
        const segLength = function(s) {
            return Math.sqrt(Math.pow(s[0] - s[2], 2) + Math.pow(s[1] - s[3], 2));
        };
        const _cloneArray = function(a) {
            return [...a];
        };
        const writeSegments = function(conn, segments, paintInfo) {
            let current = null;
            let next;
            for (let i = 0; i < segments.length - 1; i++) {
                current = current || _cloneArray(segments[i]);
                next = _cloneArray(segments[i + 1]);
                if (cornerRadius > 0 && current[4] !== next[4]) {
                    const radiusToUse = Math.min(cornerRadius, segLength(current), segLength(next));
                    // right angle. adjust current segment's end point, and next segment's start point.
                    current[2] -= current[5] * radiusToUse;
                    current[3] -= current[6] * radiusToUse;
                    next[0] += next[5] * radiusToUse;
                    next[1] += next[6] * radiusToUse;
                    const ac = (current[6] === next[5] && next[5] === 1) ||
                            ((current[6] === next[5] && next[5] === 0) && current[5] !== next[6]) ||
                            (current[6] === next[5] && next[5] === -1);
                    const sgny = next[1] > current[3] ? 1 : -1;
                    const sgnx = next[0] > current[2] ? 1 : -1;
                    const sgnEqual = sgny === sgnx;
                    const cx = (sgnEqual && ac || (!sgnEqual && !ac)) ? next[0] : current[2];
                    const cy = (sgnEqual && ac || (!sgnEqual && !ac)) ? current[3] : next[1];

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
                    const dx = (current[2] === current[0]) ? 0
                        : (current[2] > current[0]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2);
                    const dy = (current[3] === current[1]) ? 0
                        : (current[3] > current[1]) ? (paintInfo.lw / 2) : -(paintInfo.lw / 2);
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
            let i;
            let j;
            let cur;
            const rect1 = conn.endpoints[0].canvas.getBoundingClientRect();
            const rect2 = conn.endpoints[1].canvas.getBoundingClientRect();
            const x = Math.min((rect1.left + rect1.right), (rect2.left + rect2.right)) / 2;
            const y = Math.min((rect1.top + rect1.bottom), (rect2.top + rect2.bottom)) / 2;
            const result = [];
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
            let borderRadius = 0;
            const maxPossibleBorderRadius = Math.min(el.offsetWidth / 2, el.offsetHeight / 2);
            const propName = ['border', directionY, directionX, 'radius'].join('-');
            const styles = window.getComputedStyle(el);
            if (styles[propName] && styles[propName] !== 'none') {
                borderRadius = Math.min(parseFloat(styles[propName]) || 0, maxPossibleBorderRadius);
            }
            return borderRadius;
        }

        function getAdjustment(el, point, direction) {
            const realX = point.x - el.offsetLeft;
            if (realX < 1 || realX > el.offsetWidth - 1) {
                return 0;
            }
            let dx;
            let borderRadius;
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
                const offsetLeft = elem.offsetLeft + source.offsetLeft;
                const offsetTop = elem.offsetTop + source.offsetTop;
                return pos[0] >= offsetLeft && pos[0] <= (offsetLeft + source.offsetWidth) &&
                    pos[1] >= offsetTop && pos[1] <= (offsetTop + source.offsetHeight);
            });
        }

        function adjustSourcePosition(paintInfo, params) {
            const elem = params.sourceEndpoint.element;
            const source = getSourceElement(elem, params.sourcePos);
            const sourceStyle = window.getComputedStyle(source);
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
            let centerX; // center of curve of rounded corner
            let centerY; // center of curve of rounded corner
            let ratio;
            const directionX = elem.offsetLeft + elem.offsetWidth / 2 - params.sourcePos[0] >= 0 ? 'left' : 'right';
            const directionY = elem.offsetTop + elem.offsetHeight / 2 - params.sourcePos[1] >= 0 ? 'top' : 'bottom';
            const radius = getBorderRadius(elem, directionY, directionX);
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
            const dx = params.sourcePos[0] - centerX;
            const dy = params.sourcePos[1] - centerY;
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

        this._compute = function(paintInfo, params, ...args) {
            if (params.sourceEndpoint.isTemporarySource || params.sourceEndpoint.getAttachedElements().length === 0 ||
                params.targetEndpoint.getAttachedElements().length === 0) {
                // in case this connection is new one or is moving to another target or source
                // use jsPlumb Flowchart connector behaviour
                adjustSourcePosition(paintInfo, params);
                return this._flowchartConnectorCompute(paintInfo, params, ...args);
            }

            // compute the rest of the line
            const points = this.smartlineManager.getConnectionPath(this, paintInfo);
            if (points.length === 0) {
                // leave everything as is
                return;
            }

            const sourcePoint = points.shift().clone();
            const targetPoint = points.pop().clone();
            const ENDPOINT_SPACE_TO_LINE = 4;

            // adjust source and target points
            sourcePoint.y += getAdjustment(params.sourceEndpoint.element, sourcePoint, 'bottom');
            targetPoint.y -= getAdjustment(params.targetEndpoint.element, targetPoint, 'top');

            // find required correction
            const correction = {
                x: Math.min(sourcePoint.x, targetPoint.x),
                y: Math.min(sourcePoint.y, targetPoint.y)
            };

            // that will be starting point of line
            paintInfo.sx = sourcePoint.x - correction.x;
            paintInfo.sy += ENDPOINT_SPACE_TO_LINE + 1;

            // set valid archors
            const oldAnchorX = params.sourceEndpoint.anchor.x;
            const oldAnchorY = params.sourceEndpoint.anchor.y;
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
            segments = [];

            if (points.length) {
                for (let i = 0; i < points.length; i++) {
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
            let _last = null;
            let _lastAxis = null;
            const s = [];
            const segs = userSuppliedSegments || segments;
            let seg;
            let axis;
            let axisIndex;
            for (let i = 0; i < segs.length; i++) {
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
            for (let i = 0; i < path.length; i++) {
                const lx = path[i].start[0];
                const ly = path[i].start[1];
                const x = path[i].end[0];
                const y = path[i].end[1];
                const o = lx === x ? 'v' : 'h';
                const sgnx = sgn(x - lx);
                const sgny = sgn(y - ly);

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

            const midx = paintInfo.startStubX + ((paintInfo.endStubX - paintInfo.startStubX) * midpoint);
            const midy = paintInfo.startStubY + ((paintInfo.endStubY - paintInfo.startStubY) * midpoint);
            const orientations = {x: [0, 1], y: [1, 0]};
            const commonStubCalculator = function() {
                return [paintInfo.startStubX, paintInfo.startStubY, paintInfo.endStubX, paintInfo.endStubY];
            };
            const stubCalculators = {
                perpendicular: commonStubCalculator,
                orthogonal: commonStubCalculator,
                opposite: function(axis) {
                    const pi = paintInfo;
                    const idx = axis === 'x' ? 0 : 1;
                    const areInProximity = {
                        x: function() {
                            return ((pi.so[idx] === 1 && (
                                ((pi.startStubX > pi.endStubX) && (pi.tx > pi.startStubX)) ||
                                        ((pi.sx > pi.endStubX) && (pi.tx > pi.sx))))) ||

                                        ((pi.so[idx] === -1 && (
                                            ((pi.startStubX < pi.endStubX) && (pi.tx < pi.startStubX)) ||
                                        ((pi.sx < pi.endStubX) && (pi.tx < pi.sx)))));
                        },
                        y: function() {
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
                            x: [
                                (paintInfo.sx + paintInfo.tx) / 2,
                                paintInfo.startStubY,
                                (paintInfo.sx + paintInfo.tx) / 2,
                                paintInfo.endStubY
                            ],
                            y: [
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

            const stubs = stubCalculators[paintInfo.anchorOrientation](paintInfo.sourceAxis);
            const idx = paintInfo.sourceAxis === 'x' ? 0 : 1;
            const oidx = paintInfo.sourceAxis === 'x' ? 1 : 0;
            const ss = stubs[idx];
            const oss = stubs[oidx];
            const es = stubs[idx + 2];
            const oes = stubs[oidx + 2];

            const lineCalculators = {
                perpendicular: function(axis) {
                    const pi = paintInfo;
                    const sis = {
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
                    const stubs = {
                        x: [[pi.startStubX, pi.endStubX], null, [pi.endStubX, pi.startStubX]],
                        y: [[pi.startStubY, pi.endStubY], null, [pi.endStubY, pi.startStubY]]
                    };
                    const midLines = {
                        x: [[midx, pi.startStubY], [midx, pi.endStubY]],
                        y: [[pi.startStubX, midy], [pi.endStubX, midy]]
                    };
                    const linesToEnd = {
                        x: [[pi.endStubX, pi.startStubY]],
                        y: [[pi.startStubX, pi.endStubY]]
                    };
                    const startToEnd = {
                        x: [[pi.startStubX, pi.endStubY], [pi.endStubX, pi.endStubY]],
                        y: [[pi.endStubX, pi.startStubY], [pi.endStubX, pi.endStubY]]
                    };
                    const startToMidToEnd = {
                        x: [[pi.startStubX, midy], [pi.endStubX, midy], [pi.endStubX, pi.endStubY]],
                        y: [[midx, pi.startStubY], [midx, pi.endStubY], [pi.endStubX, pi.endStubY]]
                    };
                    const otherStubs = {
                        x: [pi.startStubY, pi.endStubY],
                        y: [pi.startStubX, pi.endStubX]
                    };
                    const soIdx = orientations[axis][0];
                    const toIdx = orientations[axis][1];
                    const _so = pi.so[soIdx] + 1;
                    const _to = pi.to[toIdx] + 1;
                    const otherFlipped = (pi.to[toIdx] === -1 && (otherStubs[axis][1] < otherStubs[axis][0])) ||
                        (pi.to[toIdx] === 1 && (otherStubs[axis][1] > otherStubs[axis][0]));
                    const stub1 = stubs[axis][_so][0];
                    const stub2 = stubs[axis][_so][1];
                    const segmentIndexes = sis[axis][_so][_to];

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
                    const pi = paintInfo;
                    const extent = {
                        x: pi.so[0] === -1 ? Math.min(startStub, endStub) : Math.max(startStub, endStub),
                        y: pi.so[1] === -1 ? Math.min(startStub, endStub) : Math.max(startStub, endStub)
                    }[axis];

                    return {
                        x: [
                            [extent, otherStartStub],
                            [extent, otherEndStub],
                            [endStub, otherEndStub]
                        ],
                        y: [
                            [otherStartStub, extent],
                            [otherEndStub, extent],
                            [otherEndStub, endStub]
                        ]
                    }[axis];
                },
                opposite: function(axis, ss, oss, es) {
                    const pi = paintInfo;
                    const otherAxis = {x: 'y', y: 'x'}[axis];
                    const dim = {x: 'height', y: 'width'}[axis];
                    const comparator = pi['is' + axis.toUpperCase() + 'GreaterThanStubTimes2'];

                    if (params.sourceEndpoint.elementId === params.targetEndpoint.elementId) {
                        const _val = oss + ((1 - params.sourceEndpoint.anchor[otherAxis]) * params.sourceInfo[dim]) +
                            _super.maxStub;
                        return {
                            x: [
                                [ss, _val],
                                [es, _val]
                            ],
                            y: [
                                [_val, ss],
                                [_val, es]
                            ]
                        }[axis];
                    } else if (!comparator || (pi.so[idx] === 1 && ss > es) || (pi.so[idx] === -1 && ss < es)) {
                        return {
                            x: [
                                [ss, midy],
                                [es, midy]
                            ],
                            y: [
                                [midx, ss],
                                [midx, es]
                            ]
                        }[axis];
                    } else if ((pi.so[idx] === 1 && ss < es) || (pi.so[idx] === -1 && ss > es)) {
                        return {
                            x: [
                                [midx, pi.sy],
                                [midx, pi.ty]
                            ],
                            y: [
                                [pi.sx, midy],
                                [pi.tx, midy]
                            ]
                        }[axis];
                    }
                }
            };

            // add the start stub segment.
            addSegment(segments, stubs[0], stubs[1], paintInfo);

            // compute the rest of the line
            const p = lineCalculators[paintInfo.anchorOrientation](paintInfo.sourceAxis, ss, oss, es, oes);
            if (p) {
                for (let i = 0; i < p.length; i++) {
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

    function juExtend(child, parent, ...rest) {
        let i;
        parent = Object.prototype.toString.call(parent) === '[object Array]' ? parent : [parent];

        for (i = 0; i < parent.length; i++) {
            for (const j in parent[i].prototype) {
                if (parent[i].prototype.hasOwnProperty(j)) {
                    child.prototype[j] = parent[i].prototype[j];
                }
            }
        }

        const _makeFn = function(name, protoFn) {
            return function(...args) {
                for (i = 0; i < parent.length; i++) {
                    if (parent[i].prototype[name]) {
                        parent[i].prototype[name].apply(this, args);
                    }
                }
                return protoFn.apply(this, args);
            };
        };

        const _oneSet = function(fns) {
            for (const k in fns) {
                if (fns.hasOwnProperty(k)) {
                    child.prototype[k] = _makeFn(k, fns[k]);
                }
            }
        };

        if (rest.length) {
            for (i = 0; i < rest.length; i++) {
                _oneSet(rest[i]);
            }
        }

        return child;
    }

    juExtend(Smartline, jsPlumb.Connectors.AbstractConnector);
    jsPlumb.registerConnectorType(Smartline, 'Smartline');
    _.each(jsPlumb.getRenderModes(), function(renderer) {
        jsPlumb.Connectors[renderer].Smartline = function(...args) {
            Smartline.apply(this, args);
            jsPlumb.ConnectorRenderers[renderer].apply(this, args);
        };
        juExtend(jsPlumb.Connectors[renderer].Smartline, [Smartline, jsPlumb.ConnectorRenderers[renderer]]);
    });

    return Smartline;
});
