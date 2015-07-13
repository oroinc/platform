'use strict';
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var DEBUG_ENABLED = false;
var connectionDirection;
(function (connectionDirection) {
    connectionDirection[connectionDirection["LEFT_TO_RIGHT"] = 0] = "LEFT_TO_RIGHT";
    connectionDirection[connectionDirection["RIGHT_TO_LEFT"] = 1] = "RIGHT_TO_LEFT";
    connectionDirection[connectionDirection["TOP_TO_BOTTOM"] = 8] = "TOP_TO_BOTTOM";
    connectionDirection[connectionDirection["BOTTOM_TO_TOP"] = 9] = "BOTTOM_TO_TOP";
})(connectionDirection || (connectionDirection = {}));
var intervalEntryType;
(function (intervalEntryType) {
    intervalEntryType[intervalEntryType["MIN"] = 0] = "MIN";
    intervalEntryType[intervalEntryType["MAX"] = 1] = "MAX";
    intervalEntryType[intervalEntryType["INTERVAL"] = 2] = "INTERVAL";
})(intervalEntryType || (intervalEntryType = {}));
var pathChunkType;
(function (pathChunkType) {
    pathChunkType[pathChunkType["END"] = 0] = "END";
    pathChunkType[pathChunkType["CONTINUE"] = 1] = "CONTINUE";
    pathChunkType[pathChunkType["CORNER"] = 2] = "CORNER";
    pathChunkType[pathChunkType["OPPOSITE"] = 3] = "OPPOSITE";
})(pathChunkType || (pathChunkType = {}));
var intervalSiblingType;
(function (intervalSiblingType) {
    intervalSiblingType[intervalSiblingType["MIN"] = 0] = "MIN";
    intervalSiblingType[intervalSiblingType["MAX"] = 1] = "MAX";
})(intervalSiblingType || (intervalSiblingType = {}));
var isConnectionForward = {
    0: true,
    1: false,
    8: true,
    9: false
};
var oppositeDirection = {
    0: 1,
    1: 0,
    8: 9,
    9: 8
};
var graphUtil = {
    xor: function (a, b) {
        return a ? !b : b;
    }
};
var UUID = (function () {
    function UUID() {
        this.id = UUID.counter++;
    }
    UUID.counter = 0;
    return UUID;
})();
var Container = (function () {
    function Container(boxes) {
        if (boxes) {
            this.boxes = boxes;
        }
        else {
            this.boxes = [];
        }
    }
    Container.prototype.where = function (cb) {
        var i, result = [];
        for (i = 0; i < this.boxes.length; i++) {
            if (cb(this.boxes[i])) {
                result.push(this.boxes[i]);
            }
        }
        return new Container(result);
    };
    Container.prototype.reduce = function (cb, memo) {
        var i;
        for (i = 0; i < this.boxes.length; i++) {
            memo = cb(this.boxes[i], memo);
        }
        return memo;
    };
    return Container;
})();
var Turn;
(function (Turn) {
    Turn[Turn["LEFT"] = 0] = "LEFT";
    Turn[Turn["RIGHT"] = 1] = "RIGHT";
    Turn[Turn["POINT"] = 2] = "POINT";
})(Turn || (Turn = {}));
var PreferredLocation;
(function (PreferredLocation) {
    PreferredLocation[PreferredLocation["LEFT"] = 0] = "LEFT";
    PreferredLocation[PreferredLocation["RIGHT"] = 1] = "RIGHT";
    PreferredLocation[PreferredLocation["CENTER"] = 2] = "CENTER";
})(PreferredLocation || (PreferredLocation = {}));
var GraphConstant = (function () {
    function GraphConstant() {
    }
    GraphConstant.connectionWidth = 16;
    GraphConstant.cornerCost = 200;
    GraphConstant.crossRealCost = 2400; // recomended > crossPathCost * 3
    GraphConstant.crossPathCost = 600; // recomended > cornerCost * 3
    GraphConstant.turnPreference = -1; // right turn
    return GraphConstant;
})();
var AbstractLocationStop = (function () {
    function AbstractLocationStop(axis) {
        this.axis = axis;
    }
    Object.defineProperty(AbstractLocationStop.prototype, "bound", {
        get: function () {
            throw new Error('Not implemented');
        },
        enumerable: true,
        configurable: true
    });
    return AbstractLocationStop;
})();
var LeftLocationStop = (function (_super) {
    __extends(LeftLocationStop, _super);
    function LeftLocationStop() {
        _super.apply(this, arguments);
    }
    Object.defineProperty(LeftLocationStop.prototype, "previous", {
        get: function () {
            var previous = null, currentBound = -Infinity, axisesAtLeft = this.axis.getCriticalAxisesAtLeft();
            for (var i = axisesAtLeft.length - 1; i >= 0; i--) {
                var axis = axisesAtLeft[i];
                var currentDirective = axis.getLeftLocationStop();
                if (currentDirective.bound > currentBound) {
                    previous = currentDirective;
                    currentBound = currentDirective.bound;
                }
            }
            return previous;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(LeftLocationStop.prototype, "bound", {
        get: function () {
            var previous = this.previous;
            // please keep in mind that
            // axis includes connection width
            // and bound doesn't
            if (previous && previous.bound + GraphConstant.connectionWidth > this.axis.min - GraphConstant.connectionWidth) {
                return previous.bound + GraphConstant.connectionWidth;
            }
            return this.axis.min - GraphConstant.connectionWidth;
        },
        enumerable: true,
        configurable: true
    });
    return LeftLocationStop;
})(AbstractLocationStop);
var RightLocationStop = (function (_super) {
    __extends(RightLocationStop, _super);
    function RightLocationStop() {
        _super.apply(this, arguments);
    }
    Object.defineProperty(RightLocationStop.prototype, "previous", {
        get: function () {
            var previous = null, currentBound = Infinity, axisesAtRight = this.axis.getCriticalAxisesAtRight();
            for (var i = axisesAtRight.length - 1; i >= 0; i--) {
                var axis = axisesAtRight[i], currentDirective = axis.getRightLocationStop();
                if (currentDirective.bound < currentBound) {
                    previous = currentDirective;
                    currentBound = currentDirective.bound;
                }
            }
            return previous;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(RightLocationStop.prototype, "bound", {
        get: function () {
            var previous = this.previous;
            // please keep in mind that
            // axis includes connection width
            // and bound doesn't
            if (previous && previous.bound - GraphConstant.connectionWidth < this.axis.max + GraphConstant.connectionWidth) {
                return previous.bound - GraphConstant.connectionWidth;
            }
            return this.axis.max + GraphConstant.connectionWidth;
        },
        enumerable: true,
        configurable: true
    });
    return RightLocationStop;
})(AbstractLocationStop);
var Axis = (function () {
    function Axis(isHorizontal, bounds) {
        this.children = [];
        this.turnIn = Turn.POINT;
        this.turnOut = Turn.POINT;
        this.id = Axis.counter++;
        this.isHorizontal = isHorizontal;
        this.bounds = bounds || new Interval(-Infinity, Infinity);
    }
    /**
     * Adds siblings to axis
     * @param siblings
     */
    Axis.prototype.add = function (siblings) {
        siblings.first.axis = this;
        siblings.second.axis = this;
        this.children.unshift(siblings);
        this.updateMinimalMargins();
    };
    /**
     * Returns raw preferred location based only on interval end turns
     *
     * @returns {PreferredLocation}
     */
    Axis.prototype.getRawPreferredLocation = function () {
        if (this.turnIn == Turn.POINT || this.turnOut == Turn.POINT || this.turnIn !== this.turnOut) {
            return PreferredLocation.CENTER;
        }
        return this.turnIn == Turn.LEFT ? PreferredLocation.LEFT : PreferredLocation.RIGHT;
    };
    /**
     * Returns preferred location, analyses siblings
     * @returns {PreferredLocation}
     */
    Axis.prototype.getPreferredLocation = function () {
        var preferredLocation = this.getRawPreferredLocation(), leftStop = this.getLeftLocationStop(), rightStop = this.getRightLocationStop();
        return preferredLocation;
    };
    Axis.prototype.getBestDivisionCoordinate = function () {
        var coordinate;
        //console.log(this.id, PreferredLocation[this.getPreferredLocation()], this.getLeftLocationStop().bound, this.getRightLocationStop().bound);
        this.getLeftLocationStop().bound;
        this.getRightLocationStop().bound;
        switch (this.getPreferredLocation()) {
            case PreferredLocation.LEFT:
                coordinate = this.getLeftLocationStop().bound;
                break;
            case PreferredLocation.RIGHT:
                coordinate = this.getRightLocationStop().bound;
                break;
            case PreferredLocation.CENTER:
                coordinate = (this.getLeftLocationStop().bound + this.getRightLocationStop().bound) / 2;
                break;
        }
        return coordinate;
    };
    Axis.prototype.getLeftLocationStop = function () {
        return new LeftLocationStop(this);
    };
    Axis.prototype.getRightLocationStop = function () {
        return new RightLocationStop(this);
    };
    Axis.prototype.updateMinimalMargins = function () {
        var _this = this;
        this.updateBorders();
        this.forEachDivision(function (division) {
            var child = division.first.base;
            division.first.minimalMargin = _this.min - child.min;
            division.second.minimalMargin = child.max - _this.max;
        });
    };
    Axis.prototype.updateBorders = function () {
        var min = -Infinity, max = Infinity;
        this.forEachDivision(function (division) {
            var currentMax = division.second.max - division.second.takenWidth;
            if (currentMax < max) {
                max = currentMax;
            }
            var currentMin = division.first.min + division.first.takenWidth;
            if (currentMin > min) {
                min = currentMin;
            }
        });
        this.min = min;
        this.max = max;
    };
    Axis.prototype.forEachDivision = function (cb) {
        for (var i = this.children.length - 1; i >= 0; i--) {
            var siblings = this.children[i];
            cb(siblings);
        }
    };
    Axis.prototype.isAxisAtLeft = function (needle) {
        var result = false;
        this.eachAxisAtLeft(function (axis) {
            if (axis === needle) {
                result = true;
                return true;
            }
        });
        return result;
    };
    Axis.prototype.isAxisAtRight = function (needle) {
        var result = false;
        this.eachAxisAtRight(function (axis) {
            if (axis === needle) {
                result = true;
                return true;
            }
        });
        return result;
    };
    Axis.prototype.eachAxisAtLeft = function (cb) {
        var queue = [this], processed = [], criticalAxises, current;
        while (current = queue.shift()) {
            processed.push(current);
            criticalAxises = current.getCriticalAxisesAtLeft();
            for (var i = criticalAxises.length - 1; i >= 0; i--) {
                var axis = criticalAxises[i];
                if (processed.indexOf(axis) == -1) {
                    processed.push(axis);
                    if (cb(axis)) {
                        return;
                    }
                }
            }
        }
    };
    Axis.prototype.eachAxisAtRight = function (cb) {
        var queue = [this], processed = [], criticalAxises, current;
        while (current = queue.shift()) {
            processed.push(current);
            criticalAxises = current.getCriticalAxisesAtRight();
            for (var i = criticalAxises.length - 1; i >= 0; i--) {
                var axis = criticalAxises[i];
                if (processed.indexOf(axis) == -1) {
                    processed.push(axis);
                    if (cb(axis)) {
                        return;
                    }
                }
            }
        }
    };
    Axis.prototype.getCriticalAxisesAtLeft = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        for (var i = this.children.length - 1; i >= 0; i--) {
            var siblings = this.children[i], found = false;
            if (siblings.first.divisions.length) {
                for (var j = siblings.first.divisions.length - 1; j >= 0; j--) {
                    var childDivision = siblings.first.divisions[j];
                    if (childDivision.second.axis) {
                        var rightAxises = childDivision.second.getChildAxisesAtRight();
                        if (rightAxises.length) {
                            for (var k = rightAxises.length - 1; k >= 0; k--) {
                                uniquePush(rightAxises[k]);
                                found = true;
                            }
                        }
                        else {
                            uniquePush(childDivision.second.axis);
                            found = true;
                        }
                    }
                    else {
                        // find first RightSharedInterval in parents
                        var current = childDivision.first.base;
                        while (current && current instanceof LeftSharedInterval) {
                            current = current.base;
                        }
                        if (current && current instanceof RightSharedInterval) {
                            uniquePush(current.axis);
                        }
                    }
                }
            }
            if (!found) {
                // find first LeftSharedInterval in parents
                var current = siblings.first.base;
                while (current && current instanceof LeftSharedInterval) {
                    current = current.base;
                }
                if (current && current instanceof RightSharedInterval) {
                    uniquePush(current.axis);
                }
            }
        }
        return result;
    };
    Axis.prototype.getCriticalAxisesAtRight = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        for (var i = this.children.length - 1; i >= 0; i--) {
            var siblings = this.children[i], found = false;
            if (siblings.second.divisions.length) {
                for (var j = siblings.second.divisions.length - 1; j >= 0; j--) {
                    var childDivision = siblings.second.divisions[j];
                    if (childDivision.first.axis) {
                        var leftAxises = childDivision.first.getChildAxisesAtLeft();
                        if (leftAxises.length) {
                            for (var k = leftAxises.length - 1; k >= 0; k--) {
                                uniquePush(leftAxises[k]);
                                found = true;
                            }
                        }
                        else {
                            uniquePush(childDivision.first.axis);
                            found = true;
                        }
                    }
                    else {
                        // find first LeftSharedInterval in parents
                        var current = childDivision.first.base;
                        while (current && current instanceof RightSharedInterval) {
                            current = current.base;
                        }
                        if (current && current instanceof LeftSharedInterval) {
                            uniquePush(current.axis);
                        }
                    }
                }
            }
            if (!found) {
                // find first LeftSharedInterval in parents
                var current = siblings.second.base;
                while (current && current instanceof RightSharedInterval) {
                    current = current.base;
                }
                if (current && current instanceof LeftSharedInterval) {
                    uniquePush(current.axis);
                }
            }
        }
        return result;
    };
    Axis.prototype.getChildAxisesAtLeft = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        function recur(axis, add) {
            if (add === void 0) { add = true; }
            for (var i = axis.children.length - 1; i >= 0; i--) {
                var siblings = axis.children[i];
                if (siblings.first.divisions.length) {
                    for (var j = siblings.first.divisions.length - 1; j >= 0; j--) {
                        var childDivision = siblings.first.divisions[j];
                        if (childDivision.first.axis) {
                            add = false;
                            recur(childDivision.first.axis);
                        }
                    }
                }
            }
            if (add) {
                uniquePush(axis);
            }
        }
        recur(this, false);
        return result;
    };
    Axis.prototype.getChildAxisesAtRight = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        function recur(axis, add) {
            if (add === void 0) { add = true; }
            for (var i = axis.children.length - 1; i >= 0; i--) {
                var siblings = axis.children[i];
                if (siblings.second.divisions.length) {
                    for (var j = siblings.second.divisions.length - 1; j >= 0; j--) {
                        var childDivision = siblings.second.divisions[j];
                        if (childDivision.second.axis) {
                            add = false;
                            recur(childDivision.second.axis);
                        }
                    }
                }
            }
            if (add) {
                uniquePush(axis);
            }
        }
        recur(this, false);
        return result;
    };
    Axis.counter = 0;
    return Axis;
})();
var AbstractInterval = (function () {
    function AbstractInterval() {
        this.entryType = intervalEntryType.INTERVAL;
        this.divisions = [];
        /* protected */ this._minWidth = 0;
        this.id = AbstractInterval.counter++;
    }
    Object.defineProperty(AbstractInterval.prototype, "rawMin", {
        get: function () {
            return this.min;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(AbstractInterval.prototype, "rawMax", {
        get: function () {
            return this.max;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(AbstractInterval.prototype, "center", {
        get: function () {
            return (this.min + this.max) / 2;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(AbstractInterval.prototype, "minWidth", {
        get: function () {
            return this._minWidth;
        },
        set: function (value) {
            this._minWidth = value;
        },
        enumerable: true,
        configurable: true
    });
    AbstractInterval.prototype.getSiblingMinWidth = function (siblingType) {
        var widthes = this.divisions
            .map(function (siblings) { return siblingType == intervalSiblingType.MIN ? siblings.second : siblings.first; })
            .map(function (interval) { return interval ? interval.minWidth : -Infinity; });
        return Math.max.apply(Math, widthes);
    };
    Object.defineProperty(AbstractInterval.prototype, "width", {
        get: function () {
            return this.max - this.min;
        },
        set: function (v) {
            this.max = v - this.min;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(AbstractInterval.prototype, "isValid", {
        get: function () {
            return this.max > this.min;
        },
        enumerable: true,
        configurable: true
    });
    AbstractInterval.prototype.intersection = function (s) {
        return new Interval(Math.max(this.min, s.min), Math.min(this.max, s.max));
    };
    AbstractInterval.prototype.contains = function (coordinate) {
        return (coordinate < this.min) ? false : coordinate <= this.max;
    };
    AbstractInterval.prototype.continueTo = function (dest) {
        var result = {
            distance: 0,
            interval: dest.clone()
        };
        result.interval.entryType = this.entryType;
        switch (this.entryType) {
            case intervalEntryType.MIN:
                if (dest.min >= this.min) {
                    result.distance = dest.min - this.min;
                }
                else {
                    if (dest.max > this.min) {
                        result.interval.entryType = intervalEntryType.INTERVAL;
                        result.interval.entryInterval = new Interval(this.min, dest.max);
                    }
                    else {
                        result.distance = Math.max(0, this.min - dest.max);
                        result.interval.entryType = intervalEntryType.MAX;
                    }
                }
                break;
            case intervalEntryType.MAX:
                if (dest.max <= this.max) {
                    result.distance = this.max - dest.max;
                }
                else {
                    if (dest.min < this.max) {
                        result.interval.entryType = intervalEntryType.INTERVAL;
                        result.interval.entryInterval = new Interval(dest.min, this.max);
                    }
                    else {
                        result.distance = Math.max(0, dest.min - this.max);
                        result.interval.entryType = intervalEntryType.MIN;
                    }
                }
                break;
            case intervalEntryType.INTERVAL:
                var intersection = this.entryInterval.intersection(dest);
                if (intersection.isValid) {
                    result.interval.entryInterval = intersection;
                }
                else {
                    if (this.entryInterval.min < dest.min) {
                        result.interval.entryType = intervalEntryType.MIN;
                        result.distance = dest.min - this.entryInterval.max;
                    }
                    else {
                        result.interval.entryType = intervalEntryType.MAX;
                        result.distance = this.entryInterval.min - dest.max;
                    }
                }
                break;
            default:
                throw new Error("Unknown interval entry type");
        }
        if (DEBUG_ENABLED) {
            Object.freeze(result.interval);
        }
        return result;
    };
    AbstractInterval.prototype.distanceTo = function (coordinate) {
        switch (this.entryType) {
            case intervalEntryType.MIN:
                return Math.abs(coordinate - this.min);
            case intervalEntryType.MAX:
                return Math.abs(coordinate - this.max);
            case intervalEntryType.INTERVAL:
                return (coordinate < this.entryInterval.min) ? this.entryInterval.min - coordinate : ((coordinate > this.entryInterval.max) ? coordinate - this.entryInterval.max : 0);
            default:
                throw new Error("Unknown interval entry type");
        }
    };
    AbstractInterval.prototype.clone = function () {
        return new Interval(this.min, this.max, this.entryType, this.entryInterval !== this ? this.entryInterval : undefined);
    };
    AbstractInterval.prototype.toString = function () {
        return "Interval(" + this.min + "-" + this.max + ")";
    };
    AbstractInterval.prototype.createSharedSiblings = function (constantMargin, axis) {
        var siblings = {
            first: null,
            second: null
        };
        if (!axis) {
            throw new Error("Cannot divide without axis");
        }
        else {
            siblings.first = new LeftSharedInterval(this, constantMargin, axis.bounds.min - this.min + constantMargin);
            siblings.second = new RightSharedInterval(this, constantMargin, this.max - axis.bounds.max + constantMargin);
            siblings.first.sibling = siblings.second;
            siblings.second.sibling = siblings.first;
            this.divisions.unshift(siblings);
            axis.add(siblings);
        }
        return siblings;
    };
    AbstractInterval.prototype.hasChildrenWithEntryType = function (type) {
        for (var i = this.divisions.length - 1; i >= 0; i--) {
            var current = this.divisions[i];
            if (current.first.entryType == type || current.second.entryType == type) {
                return true;
            }
            if (current.first.hasChildrenWithEntryType(type) || current.second.hasChildrenWithEntryType(type)) {
                return true;
            }
        }
        return false;
    };
    AbstractInterval.prototype.getChildAxisesAtLeft = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        function recur(interval, add) {
            for (var i = interval.divisions.length - 1; i >= 0; i--) {
                var siblings = interval.divisions[i];
                if (siblings.first.divisions.length > 0) {
                    add = false;
                    recur(siblings.first, true);
                }
                else if (siblings.first.axis) {
                    add = false;
                    uniquePush(siblings.first.axis);
                }
            }
            if (add) {
                uniquePush(interval.axis);
            }
        }
        recur(this, false);
        return result;
    };
    AbstractInterval.prototype.getChildAxisesAtRight = function () {
        var result = [];
        function uniquePush(a) {
            if (result.indexOf(a) == -1) {
                result.push(a);
            }
        }
        function recur(interval, add) {
            for (var i = interval.divisions.length - 1; i >= 0; i--) {
                var siblings = interval.divisions[i];
                if (siblings.second.divisions.length > 0) {
                    add = false;
                    recur(siblings.second, true);
                }
                else if (siblings.second.axis) {
                    add = false;
                    uniquePush(siblings.second.axis);
                }
            }
            if (add) {
                uniquePush(interval.axis);
            }
        }
        recur(this, false);
        return result;
    };
    AbstractInterval.counter = 0;
    return AbstractInterval;
})();
var Interval = (function (_super) {
    __extends(Interval, _super);
    function Interval(min, max, entryType, entryInterval) {
        if (entryType === void 0) { entryType = intervalEntryType.INTERVAL; }
        _super.call(this);
        this.min = min;
        this.max = max;
        this.entryType = entryType;
        this.entryInterval = entryInterval ? entryInterval : this;
    }
    return Interval;
})(AbstractInterval);
var SharedInterval = (function (_super) {
    __extends(SharedInterval, _super);
    function SharedInterval(base, minWidth, minimalMargin) {
        _super.call(this);
        this.base = base;
        this._minWidth = minWidth;
        this.minimalMargin = minimalMargin;
    }
    Object.defineProperty(SharedInterval.prototype, "min", {
        get: function () {
            throw new Error("Should be implemented in descendants");
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(SharedInterval.prototype, "max", {
        get: function () {
            throw new Error("Should be implemented in descendants");
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(SharedInterval.prototype, "minWidth", {
        get: function () {
            return this._minWidth;
        },
        set: function (value) {
            if (this.base instanceof SharedInterval) {
                var increment = value - this._minWidth;
                if (increment !== 0) {
                    this.base.minWidth += increment;
                }
            }
            this._minWidth = value;
            if (this.axis) {
                this.axis.updateMinimalMargins();
            }
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(SharedInterval.prototype, "takenWidth", {
        get: function () {
            return Math.max(this._minWidth, this.minimalMargin);
        },
        enumerable: true,
        configurable: true
    });
    SharedInterval.prototype.createSharedSiblings = function (constantMargin, axis) {
        this.minWidth += constantMargin;
        return _super.prototype.createSharedSiblings.call(this, constantMargin, axis);
    };
    return SharedInterval;
})(AbstractInterval);
var RightSharedInterval = (function (_super) {
    __extends(RightSharedInterval, _super);
    function RightSharedInterval() {
        _super.apply(this, arguments);
    }
    Object.defineProperty(RightSharedInterval.prototype, "min", {
        get: function () {
            return this.base.min + this.sibling.takenWidth;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(RightSharedInterval.prototype, "max", {
        get: function () {
            return this.base.max;
        },
        enumerable: true,
        configurable: true
    });
    return RightSharedInterval;
})(SharedInterval);
var LeftSharedInterval = (function (_super) {
    __extends(LeftSharedInterval, _super);
    function LeftSharedInterval() {
        _super.apply(this, arguments);
    }
    Object.defineProperty(LeftSharedInterval.prototype, "min", {
        get: function () {
            return this.base.min;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(LeftSharedInterval.prototype, "max", {
        get: function () {
            return this.base.max - this.sibling.takenWidth;
        },
        enumerable: true,
        configurable: true
    });
    return LeftSharedInterval;
})(SharedInterval);
var Rectangle = (function () {
    function Rectangle(left, top, width, height) {
        if (left === void 0) { left = 0; }
        if (top === void 0) { top = 0; }
        if (width === void 0) { width = 0; }
        if (height === void 0) { height = 0; }
        if (left instanceof AbstractInterval) {
            this.horizontalInterval = left;
            this.verticalInterval = top;
        }
        else {
            this.horizontalInterval = new Interval(left, left + width);
            this.verticalInterval = new Interval(top, top + height);
        }
    }
    Object.defineProperty(Rectangle.prototype, "left", {
        get: function () {
            return this.horizontalInterval.min;
        },
        set: function (value) {
            this.horizontalInterval.min = value;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "right", {
        get: function () {
            return this.horizontalInterval.max;
        },
        set: function (value) {
            this.horizontalInterval.max = value;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "top", {
        get: function () {
            return this.verticalInterval.min;
        },
        set: function (value) {
            this.verticalInterval.min = value;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "bottom", {
        get: function () {
            return this.verticalInterval.max;
        },
        set: function (value) {
            this.verticalInterval.max = value;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "width", {
        get: function () {
            return this.horizontalInterval.width;
        },
        set: function (value) {
            this.horizontalInterval.width = value;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "height", {
        get: function () {
            return this.verticalInterval.width;
        },
        set: function (value) {
            this.verticalInterval.width = value;
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.clone = function () {
        return new Rectangle(this.left, this.top, this.width, this.height);
    };
    Rectangle.prototype.cloneRectangle = function () {
        return new Rectangle(this.left, this.top, this.width, this.height);
    };
    Rectangle.prototype.intersection = function (box) {
        return new Rectangle(this.horizontalInterval.intersection(box.horizontalInterval), this.verticalInterval.intersection(box.verticalInterval));
    };
    Object.defineProperty(Rectangle.prototype, "isValid", {
        get: function () {
            return this.horizontalInterval.isValid && this.verticalInterval.isValid;
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.relative = function (parent) {
        return new Rectangle(this.left - parent.left, this.top - parent.top, this.width, this.height);
    };
    return Rectangle;
})();
var NodePath = (function () {
    function NodePath(connection, prev) {
        this.axises = [];
        if (!connection) {
            throw new Error("Please specify valid connection");
        }
        this.connection = connection;
        this.previous = prev;
        this.calculateLength();
    }
    Object.defineProperty(NodePath.prototype, "pathChunkType", {
        get: function () {
            if (!this.previous) {
                return pathChunkType.END;
            }
            if (Math.abs(this.connection.direction - this.previous.connection.direction) == 1) {
                return pathChunkType.OPPOSITE;
            }
            else if (this.connection.direction !== this.previous.connection.direction) {
                return pathChunkType.CORNER;
            }
            else {
                return pathChunkType.CONTINUE;
            }
        },
        enumerable: true,
        configurable: true
    });
    NodePath.prototype.passes = function (item) {
        var current = this;
        if (current.connection.destination == item) {
            return true;
        }
        while (current.previous) {
            if (current.connection.origin == item) {
                return true;
            }
            current = current.previous;
        }
        return false;
    };
    NodePath.prototype.calculateLength = function () {
        if (this.previous) {
            this.length = this.previous.length + this.connection.cost;
            var result = this.calculatePathLength(this.connection.oppositeConnection, this.previous.leavingInterval, this.previous.connection.oppositeConnection, this.connection.interval);
            this.length += result.distance;
            this.leavingInterval = result.interval;
        }
        else {
            this.length = this.connection.cost;
            this.leavingInterval = this.connection.interval;
            this.leavingCoordinate = this.connection.getPosition();
        }
    };
    NodePath.prototype.calculatePathLength = function (enteringConnection, enteringInterval, leavingConnection, leavingInterval) {
        // console.log("item(" + this.id + ").calc(" + enteringConnection.id + ", " + enteringInterval.toString() + ", " + leavingConnection.id + ", " + leavingInterval.toString() + ")")
        var result = {
            distance: 0,
            interval: null
        }, middleInterval;
        if (Math.abs(leavingConnection.direction - enteringConnection.direction) == 1) {
            // opposite directions
            result.distance += GraphConstant.cornerCost * 2;
            var res = enteringInterval.continueTo(leavingInterval);
            result.distance += res.distance;
            result.interval = res.interval;
            if (graphUtil.xor(enteringConnection.interval.min > leavingConnection.interval.min, enteringConnection.isForward)) {
                result.distance += 2 * GraphConstant.turnPreference;
            }
            // this doesn't change leavingCoordinate
            this.leavingCoordinate = this.previous.leavingCoordinate;
        }
        else if (leavingConnection.direction !== enteringConnection.direction) {
            // corner
            result.interval = leavingInterval.clone();
            result.distance += GraphConstant.cornerCost;
            switch (leavingConnection.direction) {
                case connectionDirection.BOTTOM_TO_TOP:
                case connectionDirection.RIGHT_TO_LEFT:
                    result.interval.entryType = intervalEntryType.MIN;
                    break;
                default:
                    result.interval.entryType = intervalEntryType.MAX;
            }
            result.distance += enteringInterval.distanceTo(enteringConnection.getPosition());
            result.distance += result.interval.distanceTo(leavingConnection.getPosition());
            if (this.isLeftTurn(enteringConnection.isHorizontal, enteringConnection.isForward, leavingConnection.isForward)) {
                result.distance += GraphConstant.turnPreference;
            }
            this.leavingCoordinate = enteringConnection.getPosition();
        }
        else {
            // continuation
            // result.distance += leavingConnection.isHorizontal ? leavingConnection.origin.width : leavingConnection.origin.height;
            this.leavingCoordinate = enteringConnection.getPosition();
            result.distance += Math.abs(this.leavingCoordinate - this.previous.leavingCoordinate);
            var intersection = enteringInterval.intersection(leavingInterval);
            if (intersection.width > GraphConstant.connectionWidth) {
                middleInterval = intersection;
            }
            else {
                result.distance += GraphConstant.cornerCost * 2;
                // turn preference is not needed because here are two opposite turns
                middleInterval = leavingInterval;
            }
            var res = enteringInterval.continueTo(middleInterval);
            result.distance += res.distance;
            result.interval = res.interval;
        }
        if (this.connection.origin.isReal) {
            result.distance += GraphConstant.crossRealCost;
        }
        if (DEBUG_ENABLED) {
            Object.freeze(result.interval);
        }
        return result;
    };
    NodePath.prototype.isLeftTurn = function (isVertical, prevForward, nextForward) {
        return graphUtil.xor(graphUtil.xor(isVertical, prevForward), nextForward);
    };
    NodePath.prototype.addConnection = function (connection) {
        return new NodePath(connection, this);
    };
    NodePath.prototype.toNodeList = function () {
        var nodeList = [], current = this;
        while (current) {
            nodeList.push(current.connection);
            current = current.previous;
        }
        return nodeList.reverse();
    };
    NodePath.prototype.toNodeIDList = function () {
        var current = this, last, nodeIDList = [];
        while (current) {
            nodeIDList.push(current.connection.destination.id);
            last = current;
            current = current.previous;
        }
        if (last) {
            nodeIDList.push(last.connection.origin.id);
        }
        return nodeIDList;
    };
    NodePath.prototype.toLengthList = function () {
        var current = this, lengthList = [];
        while (current) {
            lengthList.push(current.length);
            current = current.previous;
        }
        return lengthList;
    };
    NodePath.prototype.addAxis = function (axis) {
        if (this.axises.indexOf(axis) === -1) {
            this.axises.push(axis);
        }
        return axis;
    };
    NodePath.prototype.put = function (previousAxis, previousDivision) {
        var crossPathCost = GraphConstant.crossPathCost, firstDivision, centralDivision, secondDivision, nextGraphItem, tempConnection, isConnectionHorizontal = this.connection.isHorizontal, lastAxis, centerAxis;
        if (!previousAxis) {
            previousAxis = new Axis(this.connection.isHorizontal, this.leavingInterval);
            this.addAxis(previousAxis);
        }
        switch (this.pathChunkType) {
            case pathChunkType.CONTINUE:
                if (previousAxis.bounds.intersection(this.previous.leavingInterval).width < GraphConstant.connectionWidth) {
                    // have two corners and horizontal interval
                    lastAxis = new Axis(isConnectionHorizontal, this.previous.leavingInterval);
                    centerAxis = new Axis(!isConnectionHorizontal);
                    var isCenterForward = previousAxis.bounds.min < this.previous.leavingInterval.min;
                    previousAxis.turnOut = isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis.turnIn = !this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis.turnOut = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    lastAxis.turnIn = !isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    this.addAxis(centerAxis);
                    this.addAxis(lastAxis);
                    firstDivision = this.connection.origin.share(isConnectionHorizontal, previousAxis, 0);
                    nextGraphItem = (previousAxis.bounds.min < this.previous.leavingInterval.min) ? firstDivision.second : firstDivision.first;
                    secondDivision = nextGraphItem.share(isConnectionHorizontal, lastAxis, 0);
                    nextGraphItem = (previousAxis.bounds.min < this.previous.leavingInterval.min) ? secondDivision.first : secondDivision.second;
                    centralDivision = nextGraphItem.share(!isConnectionHorizontal, centerAxis, crossPathCost);
                    // add path cross costs
                    tempConnection = centralDivision.first.findLeavingConnectionTo(this.connection.isForward ? firstDivision.first : firstDivision.second);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    tempConnection = centralDivision.second.findLeavingConnectionTo(this.connection.isForward ? secondDivision.second : secondDivision.first);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    this.previous.put(lastAxis, secondDivision);
                    if (centerAxis.getRawPreferredLocation() !== PreferredLocation.CENTER) {
                        debugger;
                    }
                }
                else {
                    secondDivision = this.connection.origin.share(isConnectionHorizontal, previousAxis, crossPathCost);
                    this.previous.put(previousAxis, secondDivision);
                }
                break;
            case pathChunkType.OPPOSITE:
                // have two corners and horizontal interval
                if (previousAxis.bounds.min < this.previous.leavingInterval.min) {
                    var isCenterForward = true;
                    previousAxis.turnOut = isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis = new Axis(!isConnectionHorizontal);
                    centerAxis.turnIn = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis.turnOut = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    lastAxis = new Axis(isConnectionHorizontal, this.previous.leavingInterval);
                    lastAxis.turnIn = !isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    this.addAxis(centerAxis);
                    this.addAxis(lastAxis);
                    firstDivision = this.connection.origin.share(isConnectionHorizontal, previousAxis, 0);
                    secondDivision = firstDivision.second.share(isConnectionHorizontal, lastAxis, 0);
                    centralDivision = secondDivision.first.share(!isConnectionHorizontal, centerAxis, crossPathCost);
                    // add path cross costs
                    tempConnection = firstDivision.first.findLeavingConnectionTo(this.connection.isForward ? centralDivision.second : centralDivision.first);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    tempConnection = secondDivision.second.findLeavingConnectionTo(this.connection.isForward ? centralDivision.second : centralDivision.first);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    if (centerAxis.getRawPreferredLocation() === PreferredLocation.CENTER) {
                        debugger;
                    }
                }
                else {
                    var isCenterForward = false;
                    previousAxis.turnOut = isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis = new Axis(!isConnectionHorizontal);
                    centerAxis.turnIn = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    centerAxis.turnOut = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                    lastAxis = new Axis(isConnectionHorizontal, this.previous.leavingInterval);
                    lastAxis.turnIn = !isCenterForward ? Turn.RIGHT : Turn.LEFT;
                    this.addAxis(centerAxis);
                    this.addAxis(lastAxis);
                    firstDivision = this.connection.origin.share(isConnectionHorizontal, previousAxis, 0);
                    secondDivision = firstDivision.first.share(isConnectionHorizontal, lastAxis, 0);
                    centralDivision = secondDivision.second.share(!isConnectionHorizontal, centerAxis, crossPathCost);
                    // add path cross costs
                    tempConnection = firstDivision.second.findLeavingConnectionTo(this.connection.isForward ? centralDivision.second : centralDivision.first);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    tempConnection = secondDivision.first.findLeavingConnectionTo(this.connection.isForward ? centralDivision.second : centralDivision.first);
                    if (tempConnection) {
                        tempConnection.cost = crossPathCost;
                        tempConnection.oppositeConnection.cost = crossPathCost;
                    }
                    if (centerAxis.getRawPreferredLocation() === PreferredLocation.CENTER) {
                        debugger;
                    }
                }
                this.previous.put(lastAxis, secondDivision);
                break;
            case pathChunkType.CORNER:
                previousAxis.turnOut = !this.previous.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                lastAxis = new Axis(!isConnectionHorizontal, this.previous.leavingInterval);
                lastAxis.turnIn = this.connection.isForward ? Turn.RIGHT : Turn.LEFT;
                this.addAxis(lastAxis);
                firstDivision = this.connection.origin.share(isConnectionHorizontal, previousAxis, 0);
                nextGraphItem = this.previous.connection.isForward ? firstDivision.first : firstDivision.second;
                secondDivision = nextGraphItem.share(!isConnectionHorizontal, lastAxis, crossPathCost);
                // add path cross costs
                tempConnection = (this.previous.connection.isForward ? firstDivision.second : firstDivision.first).findLeavingConnectionTo(this.connection.isForward ? secondDivision.second : secondDivision.first);
                if (tempConnection) {
                    tempConnection.cost = crossPathCost;
                    tempConnection.oppositeConnection.cost = crossPathCost;
                }
                this.previous.put(lastAxis, secondDivision);
                break;
            default:
                // do nothing
                break;
        }
    };
    NodePath.prototype.toPointsArray = function (points, lastIsHorizontal) {
        var x, y, currentIntervalNo = 0, axis;
        if (!points.length) {
            switch (this.connection.direction) {
                case connectionDirection.LEFT_TO_RIGHT:
                    x = this.connection.origin.right;
                    y = this.axises[0].getBestDivisionCoordinate();
                    break;
                case connectionDirection.RIGHT_TO_LEFT:
                    x = this.connection.origin.left;
                    y = this.axises[0].getBestDivisionCoordinate();
                    break;
                case connectionDirection.BOTTOM_TO_TOP:
                    x = this.axises[0].getBestDivisionCoordinate();
                    y = this.connection.origin.top;
                    break;
                case connectionDirection.TOP_TO_BOTTOM:
                    x = this.axises[0].getBestDivisionCoordinate();
                    y = this.connection.origin.bottom;
                    break;
            }
            points.push({
                x: x,
                y: y
            });
            lastIsHorizontal = this.axises[0].isHorizontal;
            currentIntervalNo++;
        }
        // go through all saved axises
        for (; currentIntervalNo < this.axises.length; currentIntervalNo++) {
            axis = this.axises[currentIntervalNo];
            lastIsHorizontal = axis.isHorizontal;
            switch (axis.isHorizontal) {
                case false:
                    points.push({
                        x: axis.getBestDivisionCoordinate(),
                        y: points[points.length - 1].y
                    });
                    break;
                case true:
                    points.push({
                        x: points[points.length - 1].x,
                        y: axis.getBestDivisionCoordinate()
                    });
                    break;
            }
        }
        if (this.previous) {
            this.previous.toPointsArray(points, lastIsHorizontal);
        }
        else {
            // add close point
            switch (this.connection.direction) {
                case connectionDirection.LEFT_TO_RIGHT:
                    x = this.connection.origin.right;
                    y = points[points.length - 1].y;
                    break;
                case connectionDirection.RIGHT_TO_LEFT:
                    x = this.connection.origin.left;
                    y = points[points.length - 1].y;
                    break;
                case connectionDirection.BOTTOM_TO_TOP:
                    x = points[points.length - 1].x;
                    y = this.connection.origin.top;
                    break;
                case connectionDirection.TOP_TO_BOTTOM:
                    x = points[points.length - 1].x;
                    y = this.connection.origin.bottom;
                    break;
            }
            points.push({
                x: x,
                y: y
            });
        }
        return points;
    };
    NodePath.prototype.toString = function () {
        return "M " + this.toPointsArray([]).map(function (point) { return point.x + " " + point.y; }).join(" L ");
    };
    return NodePath;
})();
var RectangularGraphNode = (function (_super) {
    __extends(RectangularGraphNode, _super);
    function RectangularGraphNode(graph, left, top, width, height) {
        _super.call(this, left, top, width, height);
        this.leavingConnections = [];
        this.enteringConnections = [];
        this.isShared = false;
        this.id = UUID.counter++;
        this.graph = graph;
    }
    RectangularGraphNode.prototype.clone = function () {
        return new RectangularGraphNode(this.graph, this.horizontalInterval, this.verticalInterval);
    };
    RectangularGraphNode.prototype.disconnect = function () {
        while (this.leavingConnections.length) {
            this.leavingConnections[0].destroy();
        }
        while (this.enteringConnections.length) {
            this.enteringConnections[0].destroy();
        }
    };
    RectangularGraphNode.prototype.findLeavingConnectionTo = function (neededDestination) {
        for (var i = this.leavingConnections.length - 1; i >= 0; i--) {
            if (this.leavingConnections[i].destination == neededDestination) {
                return this.leavingConnections[i];
            }
        }
        return null;
    };
    RectangularGraphNode.prototype.setConnectionCostTo = function (to, cost) {
        this.leavingConnections.forEach(function (connection) {
            if (connection.destination == to) {
                connection.cost = cost;
                connection.oppositeConnection.cost = cost;
            }
        });
    };
    RectangularGraphNode.prototype.divideWidth = function (left) {
        if (this.width <= left || left <= 0) {
            throw new Error('Cannot divide');
        }
        // add two new
        var result = {
            first: new RectangularGraphNode(this.graph, this.left, this.top, left, this.height),
            second: new RectangularGraphNode(this.graph, this.left + left, this.top, this.width - left, this.height)
        };
        this.divideInternal(result, 0);
        this.simpleConnect(result);
        this.disconnect();
        return result;
    };
    RectangularGraphNode.prototype.divideHeight = function (top) {
        if (this.height <= top || top <= 0) {
            throw new Error('Cannot divide');
        }
        // add two new
        var result = {
            first: new RectangularGraphNode(this.graph, this.left, this.top, this.width, top),
            second: new RectangularGraphNode(this.graph, this.left, this.top + top, this.width, this.height - top)
        };
        this.divideInternal(result, 8);
        this.simpleConnect(result);
        this.disconnect();
        return result;
    };
    RectangularGraphNode.prototype.cloneConnection = function (node, connection) {
        var a = new Connection(node, connection.destination, connection.direction, connection.cost), b = new Connection(connection.destination, node, oppositeDirection[connection.direction], connection.cost);
        a.oppositeConnection = b;
        b.oppositeConnection = a;
    };
    RectangularGraphNode.prototype.internalSimpleConnect = function (node, connection) {
        var destination = connection.destination, topOrBottom = destination.left < node.right && destination.right > node.left, leftOrRight = destination.top < node.bottom && destination.bottom > node.top, a, b;
        switch (connection.direction) {
            case connectionDirection.LEFT_TO_RIGHT:
                if (leftOrRight && destination.horizontalInterval.contains(node.right) && destination.verticalInterval.intersection(node.verticalInterval)) {
                    this.cloneConnection(node, connection);
                }
                break;
            case connectionDirection.RIGHT_TO_LEFT:
                if (leftOrRight && destination.horizontalInterval.contains(node.left) && destination.verticalInterval.intersection(node.verticalInterval)) {
                    this.cloneConnection(node, connection);
                }
                break;
            case connectionDirection.TOP_TO_BOTTOM:
                if (topOrBottom && destination.verticalInterval.contains(node.bottom) && destination.horizontalInterval.intersection(node.horizontalInterval)) {
                    this.cloneConnection(node, connection);
                }
                break;
            case connectionDirection.BOTTOM_TO_TOP:
                if (topOrBottom && destination.verticalInterval.contains(node.top) && destination.horizontalInterval.intersection(node.horizontalInterval)) {
                    this.cloneConnection(node, connection);
                }
                break;
        }
    };
    ;
    RectangularGraphNode.prototype.simpleConnect = function (siblings) {
        var _this = this;
        this.leavingConnections.forEach(function (connection) {
            _this.internalSimpleConnect(siblings.first, connection);
            _this.internalSimpleConnect(siblings.second, connection);
        });
    };
    RectangularGraphNode.prototype.share = function (isHorizontal, axis, connectionCost) {
        this.isShared = true;
        this.usedAxisForShare = axis;
        if (isHorizontal) {
            this.shareHeight(axis, connectionCost);
        }
        else {
            this.shareWidth(axis, connectionCost);
        }
        // console.log(this.id, ' ::: ', this.sharedChildren.first.id, ' => ', this.sharedChildren.first.leavingConnections.map(function (conn) { return conn.destination.id; }), ',   ', this.sharedChildren.second.id, ' => ', this.sharedChildren.second.leavingConnections.map(function (conn) { return conn.destination.id; }));
        return this.sharedChildren;
    };
    RectangularGraphNode.prototype.shareWidth = function (axis, connectionCost) {
        this.isSharedHorizontally = false;
        var intervalDivision = this.horizontalInterval.createSharedSiblings(GraphConstant.connectionWidth, axis);
        this.sharedChildren = {
            first: this.clone(),
            second: this.clone()
        };
        this.sharedChildren.first.horizontalInterval = intervalDivision.first;
        this.sharedChildren.second.horizontalInterval = intervalDivision.second;
        this.sharedChildren.first.sharedParent = this;
        this.sharedChildren.second.sharedParent = this;
        this.divideInternal(this.sharedChildren, 0, connectionCost);
        this.sharedConnect(this.sharedChildren, axis);
        this.disconnect();
    };
    RectangularGraphNode.prototype.shareHeight = function (axis, connectionCost) {
        this.isSharedHorizontally = true;
        var intervalDivision = this.verticalInterval.createSharedSiblings(GraphConstant.connectionWidth, axis);
        this.sharedChildren = {
            first: this.clone(),
            second: this.clone()
        };
        this.sharedChildren.first.verticalInterval = intervalDivision.first;
        this.sharedChildren.second.verticalInterval = intervalDivision.second;
        this.sharedChildren.first.sharedParent = this;
        this.sharedChildren.second.sharedParent = this;
        this.divideInternal(this.sharedChildren, 8, connectionCost);
        this.sharedConnect(this.sharedChildren, axis);
        this.disconnect();
    };
    RectangularGraphNode.prototype.sharedConnect = function (siblings, mainAxis) {
        var _this = this;
        this.leavingConnections.forEach(function (connection) {
            if (graphUtil.xor(_this.isSharedHorizontally, connection.isHorizontal)) {
                _this.cloneConnection(isConnectionForward[connection.direction] ? siblings.second : siblings.first, connection);
            }
            else {
                // must find what connection it is related
                var usedAxis = connection.destination[_this.isSharedHorizontally ? 'verticalInterval' : 'horizontalInterval'].axis;
                if (usedAxis) {
                    if (usedAxis === mainAxis) {
                        if (connection.destination.isLeft()) {
                            _this.cloneConnection(siblings.first, connection);
                        }
                        else {
                            _this.cloneConnection(siblings.second, connection);
                        }
                    }
                    else {
                        _this.internalSimpleConnect(siblings.first, connection);
                        _this.internalSimpleConnect(siblings.second, connection);
                    }
                }
                else {
                    _this.internalSimpleConnect(siblings.first, connection);
                    _this.internalSimpleConnect(siblings.second, connection);
                }
            }
        });
    };
    RectangularGraphNode.prototype.divideInternal = function (result, isHorizontal, connectionCost) {
        if (connectionCost === void 0) { connectionCost = 0; }
        // replace
        this.graph.items.splice(this.graph.items.indexOf(this), 1);
        this.graph.items.push(result.first);
        this.graph.items.push(result.second);
        // keep connections up to date
        var a = new Connection(result.first, result.second, connectionDirection.LEFT_TO_RIGHT + isHorizontal, connectionCost), b = new Connection(result.second, result.first, connectionDirection.RIGHT_TO_LEFT + isHorizontal, connectionCost);
        a.oppositeConnection = b;
        b.oppositeConnection = a;
    };
    RectangularGraphNode.prototype.findTopParent = function () {
        var current = this;
        while (current.sharedParent) {
            current = current.sharedParent;
        }
        return current;
    };
    RectangularGraphNode.prototype.findClosestParentWithSameShareDirection = function () {
        var current = this;
        while (current.sharedParent) {
            current = current.sharedParent;
            if (current.isSharedHorizontally == this.isSharedHorizontally) {
                return current;
            }
        }
        return null;
    };
    RectangularGraphNode.prototype.getParentDivisionSide = function () {
        var current = this, prev;
        while (current.sharedParent) {
            prev = current;
            current = current.sharedParent;
            if (current.isSharedHorizontally == this.isSharedHorizontally) {
                return current.sharedChildren.first === prev;
            }
        }
        return undefined;
    };
    RectangularGraphNode.prototype.getDividedInterval = function () {
        if (this.isSharedHorizontally) {
            return this.verticalInterval;
        }
        else {
            return this.horizontalInterval;
        }
    };
    RectangularGraphNode.prototype.isLeft = function () {
        return this.sharedParent.sharedChildren.first === this;
    };
    RectangularGraphNode.prototype.isRight = function () {
        return this.sharedParent.sharedChildren.second === this;
    };
    return RectangularGraphNode;
})(Rectangle);
var Connection = (function (_super) {
    __extends(Connection, _super);
    function Connection(origin, destination, direction, cost) {
        _super.call(this);
        this.origin = origin;
        this.cost = cost;
        this.destination = destination;
        this.direction = direction;
        origin.leavingConnections.push(this);
        destination.enteringConnections.push(this);
        this.isHorizontal = this.direction < connectionDirection.TOP_TO_BOTTOM;
        this.interval = this.isHorizontal ?
            new Interval(Math.max(this.destination.top, this.origin.top), Math.min(this.destination.bottom, this.origin.bottom)) :
            new Interval(Math.max(this.destination.left, this.origin.left), Math.min(this.destination.right, this.origin.right));
        if (DEBUG_ENABLED) {
            Object.freeze(this.interval);
        }
    }
    Object.defineProperty(Connection.prototype, "isForward", {
        get: function () {
            return isConnectionForward[this.direction];
        },
        enumerable: true,
        configurable: true
    });
    Connection.prototype.destroy = function () {
        this.origin.leavingConnections.splice(this.origin.leavingConnections.indexOf(this), 1);
        this.destination.enteringConnections.splice(this.destination.enteringConnections.indexOf(this), 1);
        if (this.oppositeConnection) {
            // remove link to _this
            this.oppositeConnection.oppositeConnection = null;
            this.oppositeConnection.destroy();
        }
    };
    Connection.prototype.getPosition = function () {
        switch (this.direction) {
            case connectionDirection.BOTTOM_TO_TOP:
                return this.origin.top;
            case connectionDirection.TOP_TO_BOTTOM:
                return this.origin.bottom;
            case connectionDirection.RIGHT_TO_LEFT:
                return this.origin.left;
            case connectionDirection.LEFT_TO_RIGHT:
                return this.origin.right;
        }
        return undefined;
    };
    Connection.prototype.toString = function () {
        return "Connection(" + this.origin.id + "=>" + this.destination.id + ", direction=" + connectionDirection[this.direction] + ")";
    };
    Connection.prototype.getNaivePathLength = function (to) {
        return Math.max(0, this.destination.top - to.destination.bottom, to.destination.top - this.destination.bottom)
            + Math.max(0, this.destination.left - to.destination.right, to.destination.left - this.destination.right)
            + ((Math.abs(this.direction - to.direction) == 1) ?
            (GraphConstant.cornerCost * 2) :
            (this.direction !== to.direction ? GraphConstant.cornerCost : 0));
    };
    return Connection;
})(UUID);
var Graph = (function () {
    function Graph() {
        this.items = [];
        this.items.push(new RectangularGraphNode(this, -100000, -100000, 200000, 200000));
    }
    Graph.prototype.get = function (id) {
        var i;
        for (i = 0; i < this.items.length; i++) {
            if (this.items[i].id === id) {
                return this.items[i];
            }
        }
        return null;
    };
    Graph.prototype.findRawConnection = function (fromId, toId) {
        var i, selected;
        for (i = 0; i < this.items.length; i++) {
            if (this.items[i].id === fromId) {
                selected = this.items[i];
                break;
            }
        }
        if (selected) {
            for (i = 0; i < selected.leavingConnections.length; i++) {
                var connection = selected.leavingConnections[i];
                if (connection.destination.id === toId) {
                    return connection;
                }
            }
        }
        return null;
    };
    Graph.prototype.getNodeById = function (id) {
        var i, selected;
        for (i = 0; i < this.items.length; i++) {
            if (this.items[i].id === id) {
                selected = this.items[i];
                break;
            }
        }
        return selected;
    };
    Graph.prototype.findLeavingConnectionsByCid = function (cid, direction) {
        var i, result = [];
        for (i = 0; i < this.items.length; i++) {
            if (this.items[i].cid === cid) {
                var selected = this.items[i];
                for (var j = 0; j < selected.leavingConnections.length; j++) {
                    var connection = selected.leavingConnections[j];
                    if (connection.direction === direction) {
                        result.push(connection);
                    }
                }
            }
        }
        return result;
    };
    Graph.prototype.findEnteringConnectionsByCid = function (cid, direction) {
        var i, result = [];
        for (i = 0; i < this.items.length; i++) {
            if (this.items[i].cid === cid) {
                var selected = this.items[i];
                for (var j = 0; j < selected.enteringConnections.length; j++) {
                    var connection = selected.enteringConnections[j];
                    if (connection.direction === direction) {
                        result.push(connection);
                    }
                }
            }
        }
        return result;
    };
    Graph.prototype.createPath = function () {
        var ids = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            ids[_i - 0] = arguments[_i];
        }
        if (ids.length < 2) {
            throw new Error("buildPath() requires at least two arguments");
        }
        var i, currentPath = new NodePath(this.findRawConnection(ids[0], ids[1]), null);
        for (i = 2; i < ids.length; i++) {
            currentPath = currentPath.addConnection(this.findRawConnection(ids[i - 1], ids[i]));
        }
        return currentPath;
    };
    return Graph;
})();
var GraphBuilder = (function () {
    function GraphBuilder() {
    }
    GraphBuilder.prototype.build = function (c) {
        var _this = this;
        var graph = new Graph();
        c.boxes.forEach(function (box) { return _this.put(graph, box); });
        return graph;
    };
    GraphBuilder.prototype.put = function (graph, box) {
        var _this = this;
        // find all intersection of Box with current elements
        graph.items.forEach(function (item) {
            // if it is Real we should not do anything - ignore
            if (item.isReal) {
                return;
            }
            var intersection = item.intersection(box);
            if (intersection.isValid) {
                var node = _this.putPart({
                    item: item,
                    box: intersection
                });
                node.cid = box.cid;
            }
        });
    };
    GraphBuilder.prototype.putPart = function (pair) {
        var item = pair.item, excludeBox = pair.box;
        if (excludeBox.top !== pair.item.top) {
            pair.item = pair.item.divideHeight(excludeBox.top - item.top).second;
        }
        if (excludeBox.left !== pair.item.left) {
            pair.item = pair.item.divideWidth(excludeBox.left - item.left).second;
        }
        if (excludeBox.bottom !== pair.item.bottom) {
            pair.item = pair.item.divideHeight(excludeBox.height).first;
        }
        if (excludeBox.right !== pair.item.right) {
            pair.item = pair.item.divideWidth(excludeBox.width).first;
        }
        pair.item.isReal = true;
        return pair.item;
    };
    return GraphBuilder;
})();
var Finder = (function () {
    // maxFromLength: number = 0;
    function Finder(graph) {
        this.graph = graph;
        this.clear();
    }
    Finder.prototype.addFrom = function (connection, priority, path) {
        if (priority === void 0) { priority = 0; }
        // @todo optimize this array = it could have a lot items
        // console.log(this.maxFromLength = Math.max(this.maxFromLength, this.from.length));
        if (!path) {
            path = new NodePath(connection, null);
        }
        for (var i = 0; i < this.from.length; i++) {
            if (this.from[i].priority > priority) {
                // insert before and exit
                this.from.splice(i, 0, {
                    connection: connection,
                    priority: priority,
                    path: path
                });
                return;
            }
        }
        this.from.push({
            connection: connection,
            priority: priority,
            path: path
        });
    };
    Finder.prototype.addTo = function (connection) {
        if (connection instanceof Connection) {
            this.to.push(connection);
        }
        else {
            throw new Error("Please specify valid connection");
        }
    };
    Finder.prototype.clear = function () {
        this.from = [];
        this.to = [];
        this.items = [];
    };
    Finder.prototype.find = function () {
        var _this = this;
        var result, minLen = 100000, current, operationsCount = 0, from = this.from, minConnectionPath = {};
        while (current = from.shift()) {
            if (operationsCount > 10000) {
                throw new Error("Maximum iterations limit reached" + current.priority);
            }
            var endStep = current.connection.destination;
            operationsCount++;
            endStep.leavingConnections.forEach(function (connection) {
                if (current.path.passes(connection.destination) || connection.interval.width < GraphConstant.connectionWidth) {
                    return;
                }
                if (_this.to.indexOf(connection) !== -1) {
                    var resPath = current.path.addConnection(connection), len = resPath.length;
                    // @todo add comparsion to naivePath with cycle break
                    // will work perfectly with simple paths
                    if (len < minLen) {
                        result = resPath;
                        minLen = len;
                        for (var i = 0; i < from.length; i++) {
                            if (from[i].priority > minLen) {
                                from.splice(i);
                                break;
                            }
                        }
                    }
                    return;
                }
                var newPath = current.path.addConnection(connection);
                if (newPath.length < minLen && !(minConnectionPath[connection.id] && minConnectionPath[connection.id] < newPath.length)) {
                    minConnectionPath[connection.id] = newPath.length;
                    _this.addFrom(connection, _this.getPriority(newPath), newPath);
                }
            });
        }
        this.operationsCount = operationsCount;
        return result;
    };
    Finder.prototype.getPriority = function (newPath) {
        return newPath.length + this.to[0].getNaivePathLength(newPath.connection);
    };
    return Finder;
})();
//# sourceMappingURL=Graph.js.map

define({});
/*jslint ignore:end*/
