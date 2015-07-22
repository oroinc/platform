'use strict';
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var DEBUG_ENABLED = false;
var GraphConstant = (function () {
    function GraphConstant() {
    }
    GraphConstant.recommendedConnectionWidth = 16;
    GraphConstant.cornerCost = 200;
    GraphConstant.optimizationCornerCost = 190; // recomended > cornerCost - 1000 * usedAxisCostMultiplier;
    GraphConstant.crossPathCost = 300; // recomended > cornerCost * 1.5
    // static turnPreference: number = -1; // right turn
    GraphConstant.centerAxisCostMultiplier = 0.99;
    GraphConstant.usedAxisCostMultiplier = 0.99;
    GraphConstant.overBlockLineCostMultiplier = 20;
    return GraphConstant;
})();
var Point2d = (function () {
    function Point2d(x, y) {
        this.x = x;
        this.y = y;
        this._uid = Point2d.uidCounter++;
    }
    Object.defineProperty(Point2d.prototype, "id", {
        get: function () {
            return this.x * 200000 + this.y;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Point2d.prototype, "uid", {
        get: function () {
            /*if (!this._uid) {
             this._uid = Point2d.uidCounter++;
             }*/
            return this._uid;
        },
        enumerable: true,
        configurable: true
    });
    Point2d.prototype.simpleDistanceTo = function (point) {
        var dx = point.x - this.x, dy = point.y - this.y;
        return Math.abs(dx) + Math.abs(dy);
    };
    Point2d.prototype.distanceTo = function (point) {
        var dx = point.x - this.x, dy = point.y - this.y;
        return Math.sqrt(dx * dx + dy * dy);
    };
    Point2d.prototype.opposite = function () {
        return new Point2d(-this.x, -this.y);
    };
    Point2d.prototype.add = function (point) {
        return new Point2d(this.x + point.x, this.y + point.y);
    };
    Point2d.prototype.sub = function (point) {
        return new Point2d(this.x - point.x, this.y - point.y);
    };
    Point2d.prototype.mul = function (n) {
        return new Point2d(this.x * n, this.y * n);
    };
    Object.defineProperty(Point2d.prototype, "length", {
        get: function () {
            return Math.sqrt(this.x * this.x + this.y * this.y);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Point2d.prototype, "unitVector", {
        get: function () {
            var len = this.length;
            return new Point2d(this.x / len, this.y / len);
        },
        enumerable: true,
        configurable: true
    });
    Point2d.prototype.draw = function (color, radius) {
        if (color === void 0) { color = "red"; }
        if (radius === void 0) { radius = 2; }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><circle fill="' + color + '" r="' + radius + '" ' +
            'cx="' + this.x + '" cy="' + this.y + '"></circle></svg>');
    };
    Point2d.prototype.drawText = function (text, color) {
        if (color === void 0) { color = "black"; }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><text x="' + (this.x + 5) + '" y="' + (this.y - 5) + '" fill="' + color + '" font-size="10">' + text + '</text></svg>');
    };
    Point2d.prototype.rot90 = function () {
        return new Point2d(this.y, -this.x);
    };
    Point2d.prototype.rot180 = function () {
        return new Point2d(-this.x, -this.y);
    };
    Point2d.prototype.rot270 = function () {
        return new Point2d(-this.y, this.x);
    };
    Point2d.prototype.abs = function () {
        return new Point2d(Math.abs(this.x), Math.abs(this.y));
    };
    Point2d.prototype.clone = function () {
        return new Point2d(this.x, this.y);
    };
    Point2d.uidCounter = 0;
    return Point2d;
})();
var Direction2d = {
    LEFT_TO_RIGHT: new Point2d(1, 0),
    RIGHT_TO_LEFT: new Point2d(-1, 0),
    TOP_TO_BOTTOM: new Point2d(0, 1),
    BOTTOM_TO_TOP: new Point2d(0, -1)
};
var Direction2dIds = [
    Direction2d.BOTTOM_TO_TOP.id,
    Direction2d.TOP_TO_BOTTOM.id,
    Direction2d.LEFT_TO_RIGHT.id,
    Direction2d.RIGHT_TO_LEFT.id
];
var shortDirectionUid = {};
for (var i = Direction2dIds.length - 1; i >= 0; i--) {
    shortDirectionUid[Direction2dIds[i]] = i;
}
var util = {
    xor: function (a, b) {
        return a ? !b : b;
    },
    between: function (b, a, c) {
        return (a <= b && b <= c) || (a >= b && b >= c);
    },
    betweenNonInclusive: function (b, a, c) {
        return (a < b && b < c) || (a > b && b > c);
    }
};
var UUID = (function () {
    function UUID() {
        this.id = UUID.counter++;
    }
    UUID.counter = 0;
    return UUID;
})();
var Interval1d = (function () {
    function Interval1d(min, max) {
        this.min = min;
        this.max = max;
    }
    Object.defineProperty(Interval1d.prototype, "width", {
        get: function () {
            return this.max - this.min;
        },
        set: function (v) {
            this.max = v - this.min;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Interval1d.prototype, "isValid", {
        get: function () {
            return this.max > this.min;
        },
        enumerable: true,
        configurable: true
    });
    Interval1d.prototype.intersection = function (s) {
        return new Interval1d(Math.max(this.min, s.min), Math.min(this.max, s.max));
    };
    Interval1d.prototype.union = function (s) {
        return new Interval1d(Math.min(this.min, s.min), Math.max(this.max, s.max));
    };
    Interval1d.prototype.contains = function (coordinate) {
        return (coordinate < this.min) ? false : coordinate <= this.max;
    };
    Interval1d.prototype.containsNonInclusive = function (coordinate) {
        return (coordinate <= this.min) ? false : coordinate < this.max;
    };
    Interval1d.prototype.distanceTo = function (coordinate) {
        return (coordinate < this.min) ? this.min - coordinate : (coordinate > this.max ? coordinate - this.max : 0);
    };
    return Interval1d;
})();
var Interval2d = (function () {
    function Interval2d(a, b) {
        this.a = a;
        this.b = b;
    }
    Object.defineProperty(Interval2d.prototype, "length", {
        get: function () {
            return this.a.distanceTo(this.b);
        },
        enumerable: true,
        configurable: true
    });
    Interval2d.prototype.crosses = function (interval) {
        return this.getCrossPoint(interval) !== null;
    };
    Interval2d.prototype.getCrossPoint = function (interval) {
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            var v1, v2;
            if (this.a.x !== this.b.x) {
                // compare by x
                v1 = util.between(point.x, this.a.x, this.b.x);
            }
            else {
                // compare by y
                v1 = util.between(point.y, this.a.y, this.b.y);
            }
            if (interval.a.x !== interval.b.x) {
                // compare by x
                v2 = util.between(point.x, interval.a.x, interval.b.x);
            }
            else {
                // compare by y
                v2 = util.between(point.y, interval.a.y, interval.b.y);
            }
            if (v1 && v2) {
                return point;
            }
        }
        return null;
    };
    Interval2d.prototype.crossesNonInclusive = function (interval) {
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            if (this.a.x !== this.b.x) {
                // compare by x
                return util.betweenNonInclusive(point.x, this.a.x, this.b.x);
            }
            else {
                // compare by y
                return util.betweenNonInclusive(point.y, this.a.y, this.b.y);
            }
        }
        return false;
    };
    Interval2d.prototype.crossesRect = function (rect) {
        return rect.topSide.crosses(this) ||
            rect.bottomSide.crosses(this) ||
            rect.leftSide.crosses(this) ||
            rect.rightSide.crosses(this);
    };
    Object.defineProperty(Interval2d.prototype, "line", {
        get: function () {
            return new Vector2d(this.a.x, this.a.y, this.a.sub(this.b).unitVector).line;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Interval2d.prototype, "center", {
        get: function () {
            return this.a.add(this.b).mul(0.5);
        },
        enumerable: true,
        configurable: true
    });
    Interval2d.prototype.draw = function (color) {
        if (color === void 0) { color = 'green'; }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + this.a.x + ' ' + this.a.y + ' L ' + this.b.x + ' ' + this.b.y
            + '"></path></svg>');
    };
    return Interval2d;
})();
var Rectangle = (function () {
    function Rectangle(left, top, width, height) {
        if (left === void 0) { left = 0; }
        if (top === void 0) { top = 0; }
        if (width === void 0) { width = 0; }
        if (height === void 0) { height = 0; }
        if (left instanceof Interval1d) {
            this.horizontalInterval = left;
            this.verticalInterval = top;
        }
        else {
            this.horizontalInterval = new Interval1d(left, left + width);
            this.verticalInterval = new Interval1d(top, top + height);
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
    Object.defineProperty(Rectangle.prototype, "center", {
        get: function () {
            return new Point2d((this.left + this.right) / 2, (this.top + this.bottom) / 2);
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.clone = function () {
        return new Rectangle(this.left, this.top, this.width, this.height);
    };
    Rectangle.prototype.intersection = function (box) {
        return new Rectangle(this.horizontalInterval.intersection(box.horizontalInterval), this.verticalInterval.intersection(box.verticalInterval));
    };
    Rectangle.prototype.union = function (box) {
        return new Rectangle(this.horizontalInterval.union(box.horizontalInterval), this.verticalInterval.union(box.verticalInterval));
    };
    Object.defineProperty(Rectangle.prototype, "isValid", {
        get: function () {
            return this.horizontalInterval.isValid && this.verticalInterval.isValid;
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.relative = function (point) {
        return new Rectangle(this.left - point.x, this.top - point.y, this.width, this.height);
    };
    Rectangle.prototype.distanceToPoint = function (point) {
        var dx = this.horizontalInterval.distanceTo(point.x), dy = this.verticalInterval.distanceTo(point.y);
        return Math.sqrt(dx * dx + dy * dy);
    };
    Object.defineProperty(Rectangle.prototype, "topSide", {
        get: function () {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.right, this.top));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "bottomSide", {
        get: function () {
            return new Interval2d(new Point2d(this.left, this.bottom), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "leftSide", {
        get: function () {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.left, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, "rightSide", {
        get: function () {
            return new Interval2d(new Point2d(this.right, this.top), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.eachSide = function (fn) {
        fn(this.leftSide);
        fn(this.bottomSide);
        fn(this.rightSide);
        fn(this.topSide);
    };
    Rectangle.prototype.draw = function (color) {
        if (color === void 0) { color = "violet"; }
        this.eachSide(function (side) { return side.draw(color); });
    };
    Rectangle.prototype.containsPoint = function (point) {
        return this.horizontalInterval.containsNonInclusive(point.x) && this.verticalInterval.containsNonInclusive(point.y);
    };
    return Rectangle;
})();
var Vector2d = (function () {
    function Vector2d(x, y, direction) {
        this.start = new Point2d(x, y);
        this.direction = direction;
    }
    Vector2d.prototype.crosses = function (rect) {
        return this.getCrossPointWithRect(rect) !== null;
    };
    Object.defineProperty(Vector2d.prototype, "line", {
        get: function () {
            var slope = this.direction.y / this.direction.x;
            if (slope == Infinity || slope == -Infinity) {
                return new Line2d(Infinity, this.start.x);
            }
            return new Line2d(slope, this.start.y + this.start.x * slope);
        },
        enumerable: true,
        configurable: true
    });
    Vector2d.prototype.getCrossPointWithRect = function (rect) {
        var crossPoint = null;
        switch (this.direction.y) {
            case -1:
                crossPoint = this.getCrossPointWithInterval(rect.bottomSide);
                break;
            case 1:
                crossPoint = this.getCrossPointWithInterval(rect.topSide);
                break;
        }
        switch (this.direction.x) {
            case -1:
                crossPoint = this.getCrossPointWithInterval(rect.rightSide);
                break;
            case 1:
                crossPoint = this.getCrossPointWithInterval(rect.leftSide);
                break;
        }
        return crossPoint;
    };
    Vector2d.prototype.getCrossPointWithInterval = function (interval) {
        var intersectionPoint = this.line.intersection(interval.line);
        if (!isNaN(intersectionPoint.x) && Math.abs(intersectionPoint.x) !== Infinity) {
            var relativePoint = intersectionPoint.sub(this.start);
            if ((Math.sign(relativePoint.x) == Math.sign(this.direction.x)) &&
                (Math.sign(relativePoint.y) == Math.sign(this.direction.y))) {
                if (interval.a.x !== interval.b.x) {
                    if (util.between(intersectionPoint.x, interval.a.x, interval.b.x)) {
                        return intersectionPoint;
                    }
                }
                else {
                    if (util.between(intersectionPoint.y, interval.a.y, interval.b.y)) {
                        return intersectionPoint;
                    }
                }
            }
        }
        return null;
    };
    Vector2d.prototype.draw = function (color) {
        if (color === void 0) { color = "rgba(0,0,0,0.7)"; }
        this.start.draw(color, 3);
        var interval = new Interval2d(this.start, this.start.add(this.direction.unitVector.mul(100000)));
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + interval.a.x + ' ' + interval.a.y + ' L ' + interval.b.x + ' ' + interval.b.y
            + '"></path></svg>');
    };
    return Vector2d;
})();
var Line2d = (function () {
    function Line2d(slope, intercept) {
        this.slope = slope;
        this.intercept = intercept;
    }
    Line2d.prototype.intersection = function (line) {
        if (this.slope === Infinity) {
            if (line.slope === Infinity) {
                return new Point2d(NaN, NaN);
            }
            return new Point2d(this.intercept, line.intercept + line.slope * this.intercept);
        }
        if (line.slope === Infinity) {
            return new Point2d(line.intercept, this.intercept + this.slope * line.intercept);
        }
        var x = (line.intercept - this.intercept) / (this.slope - line.slope), y = this.slope * x + this.intercept; // solce
        return new Point2d(x, y);
    };
    Line2d.prototype.draw = function (color) {
        if (color === void 0) { color = "orange"; }
        var interval;
        if (this.slope === Infinity) {
            interval = new Interval2d(new Point2d(this.intercept, -100000), new Point2d(this.intercept, 100000));
        }
        else {
            interval = new Interval2d(new Point2d(-100000, this.slope * -100000 + this.intercept), new Point2d(100000, this.slope * 100000 + this.intercept));
        }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + interval.a.x + ' ' + interval.a.y + ' L ' + interval.b.x + ' ' + interval.b.y
            + '"></path></svg>');
    };
    return Line2d;
})();
var NodePoint = (function (_super) {
    __extends(NodePoint, _super);
    function NodePoint(x, y) {
        _super.call(this, x, y);
        this._connections = [];
        this.stale = false;
        this.used = false;
    }
    Object.defineProperty(NodePoint.prototype, "connections", {
        get: function () {
            return this._connections;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(NodePoint.prototype, "recommendedX", {
        get: function () {
            if (this.vAxis) {
                var recommendation = this.vAxis.recommendedPosition;
                if (recommendation !== undefined) {
                    return recommendation;
                }
            }
            return this.x;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(NodePoint.prototype, "recommendedY", {
        get: function () {
            if (this.hAxis) {
                var recommendation = this.hAxis.recommendedPosition;
                if (recommendation !== undefined) {
                    return recommendation;
                }
            }
            return this.y;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(NodePoint.prototype, "recommendedPoint", {
        get: function () {
            return new Point2d(this.recommendedX, this.recommendedY);
        },
        enumerable: true,
        configurable: true
    });
    NodePoint.prototype.connect = function (direction, node) {
        if (this.connections[direction.id]) {
            this.connections[direction.id].remove();
        }
        if (node) {
            new Connection(this, node, direction);
        }
    };
    NodePoint.prototype.removeConnection = function (conn) {
        var index = this.connections.indexOf(conn);
        if (index !== -1) {
            this.connections[index] = null;
        }
    };
    NodePoint.prototype.eachConnection = function (fn) {
        for (var i = 0; i < Direction2dIds.length; i++) {
            var conn = this.connections[Direction2dIds[i]];
            if (conn) {
                fn(conn);
            }
        }
    };
    NodePoint.prototype.eachTraversableConnection = function (from, fn) {
        for (var i = 0; i < Direction2dIds.length; i++) {
            var conn = this.connections[Direction2dIds[i]];
            if (conn && conn !== from && conn.traversable) {
                fn(conn.second(this), conn);
            }
        }
    };
    NodePoint.prototype.clone = function () {
        var node = new NodePoint(this.x, this.y);
        node.vAxis = this.vAxis;
        node.hAxis = this.hAxis;
        return node;
    };
    NodePoint.prototype.nextNode = function (direction) {
        var connection = this.connections[direction.id];
        return connection ? connection.second(this) : null;
    };
    NodePoint.prototype.draw = function (color, radius) {
        this.recommendedPoint.draw(color, radius);
    };
    return NodePoint;
})(Point2d);
var Connection = (function (_super) {
    __extends(Connection, _super);
    function Connection(a, b, vector) {
        _super.call(this, a, b);
        this.costMultiplier = 1;
        this.traversable = true;
        this.uid = Connection.uidCounter++;
        if (!vector) {
            vector = b.sub(a).unitVector;
        }
        if (DEBUG_ENABLED) {
            if (b.sub(a).length !== 0 && vector) {
                if (vector.id !== b.sub(a).unitVector.id) {
                    debugger;
                }
            }
        }
        this.vector = vector;
        a.connections[vector.id] = this;
        b.connections[vector.rot180().id] = this;
        if (this.axis.graph.isConnectionUnderRect(this)) {
            this.costMultiplier *= GraphConstant.overBlockLineCostMultiplier;
        }
    }
    Object.defineProperty(Connection.prototype, "cost", {
        get: function () {
            return this.length * this.axis.costMultiplier * this.costMultiplier + (this.a.used || this.b.used ? GraphConstant.crossPathCost : 0);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, "axis", {
        get: function () {
            return this.a.vAxis === this.b.vAxis ? this.a.vAxis : this.a.hAxis;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, "leftSibling", {
        get: function () {
            var leftPoint = this.a.nextNode(this.vector.rot90());
            if (leftPoint && leftPoint.x == this.a.x && leftPoint.y == this.a.y) {
                return leftPoint.connections[this.directionFrom(this.a).id];
            }
            return null;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, "rightSibling", {
        get: function () {
            var rightPoint = this.a.nextNode(this.vector.rot270());
            if (rightPoint && rightPoint.x == this.a.x && rightPoint.y == this.a.y) {
                return rightPoint.connections[this.directionFrom(this.a).id];
            }
            return null;
        },
        enumerable: true,
        configurable: true
    });
    Connection.prototype.remove = function () {
        this.a.removeConnection(this);
        this.b.removeConnection(this);
    };
    Connection.prototype.second = function (first) {
        return (first === this.a) ? this.b : this.a;
    };
    Connection.prototype.directionFrom = function (first) {
        return this.b === first ? this.vector.rot180() : this.vector;
    };
    Connection.prototype.replaceNode = function (original, replacement) {
        if (this.a == original) {
            var vector = this.vector;
            replacement.connections[vector.id] = this;
            original.connections[vector.id] = null;
            this.a = replacement;
        }
        else {
            var vector = this.vector.rot180();
            replacement.connections[vector.id] = this;
            original.connections[vector.id] = null;
            this.b = replacement;
        }
    };
    Connection.prototype.draw = function (color) {
        if (color === void 0) { color = 'green'; }
        (new Interval2d(new Point2d(this.a.recommendedX, this.a.recommendedY), new Point2d(this.b.recommendedX, this.b.recommendedY))).draw(color);
    };
    Connection.uidCounter = 0;
    return Connection;
})(Interval2d);
var AbstractSimpleConstraint = (function () {
    function AbstractSimpleConstraint() {
        this.recomendedStart = undefined;
    }
    return AbstractSimpleConstraint;
})();
var LeftSimpleConstraint = (function (_super) {
    __extends(LeftSimpleConstraint, _super);
    function LeftSimpleConstraint(recomendedStart) {
        _super.call(this);
        this.recomendedStart = recomendedStart;
    }
    Object.defineProperty(LeftSimpleConstraint.prototype, "recommendedEnd", {
        get: function () {
            return this.recomendedStart + this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        },
        enumerable: true,
        configurable: true
    });
    return LeftSimpleConstraint;
})(AbstractSimpleConstraint);
var RightSimpleConstraint = (function (_super) {
    __extends(RightSimpleConstraint, _super);
    function RightSimpleConstraint(recomendedStart) {
        _super.call(this);
        this.recomendedStart = recomendedStart;
    }
    Object.defineProperty(RightSimpleConstraint.prototype, "recommendedBound", {
        get: function () {
            return this.recomendedStart + this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        },
        enumerable: true,
        configurable: true
    });
    return RightSimpleConstraint;
})(AbstractSimpleConstraint);
var EmptyConstraint = (function (_super) {
    __extends(EmptyConstraint, _super);
    function EmptyConstraint() {
        _super.apply(this, arguments);
    }
    Object.defineProperty(EmptyConstraint.prototype, "recommendedEnd", {
        get: function () {
            return undefined;
        },
        enumerable: true,
        configurable: true
    });
    return EmptyConstraint;
})(AbstractSimpleConstraint);
var AbstractLocationDirective = (function () {
    function AbstractLocationDirective() {
    }
    AbstractLocationDirective.prototype.getRecommendedPosition = function (lineNo) {
        throw new Error("That's abstract method");
    };
    return AbstractLocationDirective;
})();
var CenterLocationDirective = (function (_super) {
    __extends(CenterLocationDirective, _super);
    function CenterLocationDirective() {
        _super.apply(this, arguments);
    }
    CenterLocationDirective.prototype.getRecommendedPosition = function (lineNo) {
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        return center - requiredWidth / 2 + GraphConstant.recommendedConnectionWidth * lineNo;
    };
    return CenterLocationDirective;
})(AbstractLocationDirective);
var StickLeftLocationDirective = (function (_super) {
    __extends(StickLeftLocationDirective, _super);
    function StickLeftLocationDirective() {
        _super.apply(this, arguments);
    }
    StickLeftLocationDirective.prototype.getRecommendedPosition = function (lineNo) {
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        return center + GraphConstant.recommendedConnectionWidth * (lineNo + 0.5);
    };
    return StickLeftLocationDirective;
})(AbstractLocationDirective);
var StickRightLocationDirective = (function (_super) {
    __extends(StickRightLocationDirective, _super);
    function StickRightLocationDirective() {
        _super.apply(this, arguments);
    }
    StickRightLocationDirective.prototype.getRecommendedPosition = function (lineNo) {
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = (this.axis.linesIncluded + 0.5) * GraphConstant.recommendedConnectionWidth;
        return center - requiredWidth + GraphConstant.recommendedConnectionWidth * lineNo;
    };
    return StickRightLocationDirective;
})(AbstractLocationDirective);
var Axis = (function (_super) {
    __extends(Axis, _super);
    function Axis(a, b, graph, costMultiplier) {
        if (costMultiplier === void 0) { costMultiplier = 1; }
        _super.call(this, a, b);
        this.clonesAtLeft = [];
        this.clonesAtRight = [];
        this.used = false;
        this.nodes = [];
        this.isVertical = this.a.x === this.b.x;
        this.uid = Axis.uidCounter++;
        this.costMultiplier = costMultiplier;
        this.graph = graph;
    }
    Object.defineProperty(Axis.prototype, "connections", {
        get: function () {
            var result = [];
            var vectorId = this.b.sub(this.a).unitVector.abs().rot180().id;
            for (var i = this.nodes.length - 1; i >= 1; i--) {
                var node = this.nodes[i];
                result.push(node.connections[vectorId]);
            }
            return result;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Axis.prototype, "closestLeftClone", {
        get: function () {
            var closestLeftClone = null, leftClone = this.clonesAtLeft[0];
            while (leftClone) {
                closestLeftClone = leftClone;
                leftClone = leftClone.clonesAtRight[leftClone.clonesAtRight.length - 1];
            }
            return closestLeftClone;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Axis.prototype, "closestRightClone", {
        get: function () {
            var closestRightClone = null, rightClone = this.clonesAtRight[0];
            while (rightClone) {
                closestRightClone = rightClone;
                rightClone = rightClone.clonesAtLeft[rightClone.clonesAtLeft.length - 1];
            }
            return closestRightClone;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Axis.prototype, "allClones", {
        get: function () {
            var clones = [];
            for (var i = 0; i < this.clonesAtLeft.length; i++) {
                var clone = this.clonesAtLeft[i];
                clones.push.apply(clones, clone.allClones);
            }
            clones.push(this);
            for (var i = 0; i < this.clonesAtRight.length; i++) {
                var clone = this.clonesAtRight[i];
                clones.push.apply(clones, clone.allClones);
            }
            return clones;
        },
        enumerable: true,
        configurable: true
    });
    Axis.createFromInterval = function (interval, graph) {
        var costMultiplier = interval.costMultiplier;
        var clone = new Axis(interval.a, interval.b, graph, costMultiplier);
        return clone;
    };
    Axis.prototype.addNode = function (node) {
        if (this.isVertical) {
            node.vAxis = this;
        }
        else {
            node.hAxis = this;
        }
        if (this.nodes.indexOf(node) !== -1) {
            return;
        }
        this.nodes.push(node);
    };
    Object.defineProperty(Axis.prototype, "nextNodeConnVector", {
        get: function () {
            if (this.isVertical) {
                return Direction2d.TOP_TO_BOTTOM;
            }
            else {
                return Direction2d.LEFT_TO_RIGHT;
            }
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Axis.prototype, "prevNodeConnVector", {
        get: function () {
            if (this.isVertical) {
                return Direction2d.BOTTOM_TO_TOP;
            }
            else {
                return Direction2d.RIGHT_TO_LEFT;
            }
        },
        enumerable: true,
        configurable: true
    });
    Axis.prototype.addFinalNode = function (node) {
        var nextNodeConnVector = this.nextNodeConnVector, nextNodeConn, prevNodeConn;
        if (nextNodeConn = node.connections[nextNodeConnVector.id]) {
            var nextNode = nextNodeConn.second(node), nextIndex = this.nodes.indexOf(nextNode);
            if (nextIndex === -1)
                throw Error("Invalid add node call");
            this.nodes.splice(nextIndex, 0, node);
            if (this.nodes[nextIndex].connections[nextNodeConnVector.id].second(this.nodes[nextIndex]) !== nextNode) {
                debugger;
            }
            if (nextNode.connections[nextNodeConnVector.rot180().id].second(nextNode) !== this.nodes[nextIndex]) {
                debugger;
            }
        }
        else if (prevNodeConn = node.connections[this.prevNodeConnVector.id]) {
            var prevNode = prevNodeConn.second(node), prevIndex = this.nodes.indexOf(prevNode);
            if (prevIndex === -1)
                throw Error("Invalid add node call");
            this.nodes.splice(prevIndex + 1, 0, node);
            if (this.nodes[prevIndex].connections[nextNodeConnVector.id].second(this.nodes[prevIndex]) !== node) {
                debugger;
            }
            if (node.connections[nextNodeConnVector.rot180().id].second(node) !== this.nodes[prevIndex]) {
                debugger;
            }
        }
        else {
            throw Error("Node should be connected before addition");
        }
        if (DEBUG_ENABLED) {
            this.selfCheck();
        }
    };
    Axis.prototype.selfCheck = function () {
        var current = this.nodes[0];
        var prev = this.nodes[0];
        var nextNodeConnVector = this.b.sub(this.a).unitVector;
        var i = 0;
        while (i++, current = current.nextNode(nextNodeConnVector)) {
            if (current !== this.nodes[i]) {
                debugger;
                return false;
            }
            if (current.nextNode(nextNodeConnVector.rot180()) !== this.nodes[i - 1]) {
                debugger;
                return false;
            }
            if (current.x + current.y < prev.x + prev.y) {
                debugger;
                return false;
            }
        }
        return true;
    };
    Axis.prototype.finalize = function () {
        // this.nodes.forEach((node)=>node.draw('red'));
        var firstNode = this.nodes[0];
        var lastNode = this.nodes[this.nodes.length - 1];
        if (this.isVertical) {
            firstNode.removeConnection(firstNode.connections[Direction2d.TOP_TO_BOTTOM.id]);
            lastNode.removeConnection(firstNode.connections[Direction2d.BOTTOM_TO_TOP.id]);
            for (var i = this.nodes.length - 1; i >= 0; i--) {
                var node = this.nodes[i];
                node.vAxis = this;
                node.connect(Direction2d.BOTTOM_TO_TOP, this.nodes[i - 1]);
            }
        }
        else {
            firstNode.removeConnection(firstNode.connections[Direction2d.LEFT_TO_RIGHT.id]);
            lastNode.removeConnection(firstNode.connections[Direction2d.RIGHT_TO_LEFT.id]);
            for (var i = this.nodes.length - 1; i >= 0; i--) {
                var node = this.nodes[i];
                node.hAxis = this;
                node.connect(Direction2d.RIGHT_TO_LEFT, this.nodes[i - 1]);
            }
        }
    };
    Axis.prototype.sortNodes = function () {
        // sort nodes and connect them
        var isVertical = this.isVertical;
        this.nodes.sort(function (a, b) {
            if (a.x === b.x && a.y === b.y) {
                // virtual node
                debugger;
            }
            return a.x - b.x + a.y - b.y;
        });
    };
    Axis.prototype.merge = function (axis) {
        var middle = this.a.add(this.b).mul(0.5);
        this.a = this.a.simpleDistanceTo(middle) > axis.a.simpleDistanceTo(middle) ? this.a : axis.a;
        this.b = this.b.simpleDistanceTo(middle) > axis.b.simpleDistanceTo(middle) ? this.b : axis.b;
    };
    Axis.prototype.cloneAtDirection = function (direction) {
        var axis = Axis.createFromInterval(this, this.graph);
        for (var i = 0; i < this.nodes.length; i++) {
            var node = this.nodes[i], clonedNode = node.clone(), secondNode = node.nextNode(direction);
            if (node.used && secondNode.used) {
                clonedNode.used = true;
            }
            node.connect(direction, clonedNode);
            if (node.nextNode(direction) !== clonedNode) {
                debugger;
            }
            if (clonedNode.nextNode(direction.rot180()) !== node) {
                debugger;
            }
            if (secondNode) {
                clonedNode.connect(direction, secondNode);
                if (clonedNode.nextNode(direction) !== secondNode) {
                    debugger;
                }
                if (secondNode.nextNode(direction.rot180()) !== clonedNode) {
                    debugger;
                }
            }
            if (this.isVertical) {
                node.hAxis.addFinalNode(clonedNode);
            }
            else {
                node.vAxis.addFinalNode(clonedNode);
            }
            axis.addNode(clonedNode);
        }
        axis.finalize();
        return axis;
    };
    Axis.prototype.ensureTraversableSiblings = function () {
        var clone;
        clone = this.closestLeftClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs().rot180());
            this.clonesAtLeft.unshift(this.cloneAtDirection(this.a.sub(this.b).unitVector.rot90().abs().rot180()));
        }
        clone = this.closestRightClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs());
            this.clonesAtRight.unshift(this.cloneAtDirection(this.a.sub(this.b).unitVector.rot90().abs()));
        }
    };
    Axis.uidCounter = 0;
    return Axis;
})(Interval2d);
var BaseAxis = (function (_super) {
    __extends(BaseAxis, _super);
    function BaseAxis(a, b, graph, costMultiplier, leftConstraint, rightConstraint, locationDirective) {
        _super.call(this, a, b, graph, costMultiplier);
        this.linesIncluded = 1;
        leftConstraint.axis = this;
        rightConstraint.axis = this;
        locationDirective.axis = this;
        this.leftConstraint = leftConstraint;
        this.rightConstraint = rightConstraint;
        this.locationDirective = locationDirective;
    }
    BaseAxis.createFromInterval = function (interval, graph, leftConstraint, rightConstraint, locationDirective) {
        var costMultiplier = interval.costMultiplier;
        var clone = new BaseAxis(interval.a, interval.b, graph, costMultiplier, leftConstraint, rightConstraint, locationDirective);
        return clone;
    };
    return BaseAxis;
})(Axis);
var Graph = (function () {
    function Graph() {
        this.rectangles = [];
        this.baseAxises = [];
        this.horizontalAxises = [];
        this.verticalAxises = [];
        this.nodes = {};
        this.mergeAxisesQueue = [];
        this.axisesConnectedAtLeft = [];
        this.axisesConnectedAtRight = [];
        this.centerLineMinimalRequiredWidth = 32;
    }
    Graph.prototype.build = function () {
        this.outerRect = this.rectangles.reduce(function (prev, current) { return current.union(prev); }, new Rectangle(this.rectangles[0].top, this.rectangles[0].left, 0, 0));
        this.baseAxises.push(BaseAxis.createFromInterval(this.outerRect.topSide, this, new EmptyConstraint(), new RightSimpleConstraint(this.outerRect.top), new StickRightLocationDirective()), BaseAxis.createFromInterval(this.outerRect.bottomSide, this, new LeftSimpleConstraint(this.outerRect.bottom), new EmptyConstraint(), new StickLeftLocationDirective()), BaseAxis.createFromInterval(this.outerRect.leftSide, this, new EmptyConstraint(), new RightSimpleConstraint(this.outerRect.left), new StickRightLocationDirective()), BaseAxis.createFromInterval(this.outerRect.rightSide, this, new LeftSimpleConstraint(this.outerRect.right), new EmptyConstraint(), new StickLeftLocationDirective()));
        this.buildCornerAxises();
        this.buildCenterAxises();
        this.buildCenterLinesBetweenNodes();
        this.createAxises();
        this.buildMergeRequests();
        this.mergeAxises();
        this.buildNodes();
        this.finalizeAxises();
        if (DEBUG_ENABLED) {
            this.draw();
        }
    };
    Graph.prototype.getPathFromCid = function (cid, direction) {
        return this.getPathFrom(this.getRectByCid(cid), direction);
    };
    Graph.prototype.getPathFrom = function (rect, direction) {
        var center = rect.center, node;
        switch (direction.id) {
            case Direction2d.BOTTOM_TO_TOP.id:
                node = this.getNodeAt(new Point2d(center.x, center.y - 1));
                break;
            case Direction2d.TOP_TO_BOTTOM.id:
                node = this.getNodeAt(new Point2d(center.x, center.y + 1));
                break;
            case Direction2d.LEFT_TO_RIGHT.id:
                node = this.getNodeAt(new Point2d(center.x - 1, center.y));
                break;
            case Direction2d.RIGHT_TO_LEFT.id:
                node = this.getNodeAt(new Point2d(center.x + 1, center.y));
                break;
            default:
                throw new Error("Not supported direction");
        }
        return new Path(node.connections[direction.id], node, null);
    };
    Graph.prototype.getRectByCid = function (cid) {
        for (var i = 0; i < this.rectangles.length; i++) {
            var rect = this.rectangles[i];
            if (rect.cid === cid) {
                return rect;
            }
        }
        return null;
    };
    Graph.prototype.draw = function () {
        var i;
        this.outerRect.draw('red');
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].draw("cyan");
        }
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].draw("cyan");
        }
        for (i = this.rectangles.length - 1; i >= 0; i--) {
            this.rectangles[i].draw("black");
        }
        for (var key in this.nodes) {
            this.nodes[key].draw("black");
        }
    };
    Graph.prototype.createAxises = function () {
        for (var i = 0; i < this.baseAxises.length; i++) {
            var axis = this.baseAxises[i];
            if (axis.a.x === axis.b.x) {
                this.verticalAxises.push(BaseAxis.createFromInterval(axis, this, axis.leftConstraint, axis.rightConstraint, axis.locationDirective));
            }
            else if (axis.a.y === axis.b.y) {
                this.horizontalAxises.push(BaseAxis.createFromInterval(axis, this, axis.leftConstraint, axis.rightConstraint, axis.locationDirective));
            }
            else {
                throw Error("Not supported");
            }
        }
    };
    Graph.prototype.removeAxis = function (axis) {
        var index;
        if ((index = this.horizontalAxises.indexOf(axis)) !== -1) {
            this.horizontalAxises.splice(index, 1);
            return;
        }
        if ((index = this.verticalAxises.indexOf(axis)) !== -1) {
            this.verticalAxises.splice(index, 1);
        }
    };
    Graph.prototype.mergeAxises = function () {
        var i, j;
        for (i = 0; i < this.mergeAxisesQueue.length; i++) {
            var queue = this.mergeAxisesQueue[i];
            for (j = queue.length - 1; j >= 1; j--) {
                queue[j - 1].merge(queue[j]);
                this.removeAxis(queue[j]);
            }
        }
    };
    Graph.prototype.finalizeAxises = function () {
        var i;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].sortNodes();
            this.verticalAxises[i].finalize();
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].sortNodes();
            this.horizontalAxises[i].finalize();
        }
        this.verticalAxises.sort(function (a, b) { return a.a.x - b.a.x; });
        this.horizontalAxises.sort(function (a, b) { return a.a.y - b.a.y; });
        // find connections between axises
        for (i = 0; i < this.horizontalAxises.length; i++) {
            var axis = this.horizontalAxises[i];
            for (var j = axis.nodes.length - 1; j >= 0; j--) {
                var node = axis.nodes[j], connectionToLeft = node.connections[Direction2d.TOP_TO_BOTTOM.id], nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null, connectionToRight = node.connections[Direction2d.BOTTOM_TO_TOP.id], nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                if (nodeAtLeft) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, nodeAtLeft.hAxis);
                }
                if (nodeAtRight) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, nodeAtRight.hAxis);
                }
            }
        }
        for (i = 0; i < this.verticalAxises.length; i++) {
            var axis = this.verticalAxises[i];
            for (var j = axis.nodes.length - 1; j >= 0; j--) {
                var node = axis.nodes[j], connectionToLeft = node.connections[Direction2d.RIGHT_TO_LEFT.id], nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null, connectionToRight = node.connections[Direction2d.LEFT_TO_RIGHT.id], nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                if (nodeAtLeft) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, nodeAtLeft.vAxis);
                }
                if (nodeAtRight) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, nodeAtRight.vAxis);
                }
            }
        }
    };
    Graph.prototype.addAxisConnectionInfo = function (keeper, main, secondary) {
        if (!keeper[main.uid]) {
            keeper[main.uid] = [];
        }
        if (keeper[main.uid].indexOf(secondary) === -1) {
            keeper[main.uid].push(secondary);
        }
    };
    Graph.prototype.hasAxis = function (axis) {
        return this.horizontalAxises.indexOf(axis) !== -1 || this.verticalAxises.indexOf(axis) !== -1;
    };
    Graph.prototype.buildCornerAxises = function () {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect = this.rectangles[i];
            var defs = [
                {
                    vectorA: new Vector2d(rect.left, rect.top, Direction2d.TOP_TO_BOTTOM),
                    vectorB: new Vector2d(rect.left, rect.bottom, Direction2d.BOTTOM_TO_TOP),
                    leftConstraint: new EmptyConstraint(),
                    rightConstraint: new RightSimpleConstraint(rect.left),
                    locationDirective: new StickRightLocationDirective()
                },
                {
                    vectorA: new Vector2d(rect.right, rect.top, Direction2d.TOP_TO_BOTTOM),
                    vectorB: new Vector2d(rect.right, rect.bottom, Direction2d.BOTTOM_TO_TOP),
                    leftConstraint: new LeftSimpleConstraint(rect.right),
                    rightConstraint: new EmptyConstraint(),
                    locationDirective: new StickLeftLocationDirective()
                },
                {
                    vectorA: new Vector2d(rect.left, rect.top, Direction2d.RIGHT_TO_LEFT),
                    vectorB: new Vector2d(rect.right, rect.top, Direction2d.LEFT_TO_RIGHT),
                    leftConstraint: new EmptyConstraint(),
                    rightConstraint: new RightSimpleConstraint(rect.top),
                    locationDirective: new StickRightLocationDirective()
                },
                {
                    vectorA: new Vector2d(rect.left, rect.bottom, Direction2d.RIGHT_TO_LEFT),
                    vectorB: new Vector2d(rect.right, rect.bottom, Direction2d.LEFT_TO_RIGHT),
                    leftConstraint: new LeftSimpleConstraint(rect.bottom),
                    rightConstraint: new EmptyConstraint(),
                    locationDirective: new StickLeftLocationDirective()
                }
            ];
            for (var j = defs.length - 1; j >= 0; j--) {
                var def = defs[j];
                var closestRectCrossPoint1 = this.findClosestRectCross(def.vectorA, rect), closestRectCrossPoint2 = this.findClosestRectCross(def.vectorB, rect);
                this.baseAxises.push(BaseAxis.createFromInterval(new Interval2d(closestRectCrossPoint1, closestRectCrossPoint2), this, def.leftConstraint, def.rightConstraint, def.locationDirective));
            }
        }
    };
    Graph.prototype.buildCenterAxises = function () {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect = this.rectangles[i];
            var center = rect.center;
            var defs = [
                {
                    vector: new Vector2d(center.x, center.y + 1, Direction2d.TOP_TO_BOTTOM),
                    leftConstraint: new LeftSimpleConstraint(rect.left),
                    rightConstraint: new RightSimpleConstraint(rect.right),
                    locationDirective: new CenterLocationDirective()
                },
                {
                    vector: new Vector2d(center.x, center.y - 1, Direction2d.BOTTOM_TO_TOP),
                    leftConstraint: new LeftSimpleConstraint(rect.left),
                    rightConstraint: new RightSimpleConstraint(rect.right),
                    locationDirective: new CenterLocationDirective()
                },
                {
                    vector: new Vector2d(center.x + 1, center.y, Direction2d.RIGHT_TO_LEFT),
                    leftConstraint: new LeftSimpleConstraint(rect.top),
                    rightConstraint: new RightSimpleConstraint(rect.bottom),
                    locationDirective: new CenterLocationDirective()
                },
                {
                    vector: new Vector2d(center.x - 1, center.y, Direction2d.LEFT_TO_RIGHT),
                    leftConstraint: new LeftSimpleConstraint(rect.top),
                    rightConstraint: new RightSimpleConstraint(rect.bottom),
                    locationDirective: new CenterLocationDirective()
                }
            ];
            for (var j = defs.length - 1; j >= 0; j--) {
                var def = defs[j];
                var closestRectCrossPoint = this.findClosestRectCross(def.vector, rect);
                this.baseAxises.push(BaseAxis.createFromInterval(new Interval2d(def.vector.start, closestRectCrossPoint), this, def.leftConstraint, def.rightConstraint, def.locationDirective));
            }
        }
    };
    Graph.prototype.eachRectanglePair = function (fn) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect1 = this.rectangles[i];
            for (var j = i - 1; j >= 0; j--) {
                fn(rect1, this.rectangles[j]);
            }
        }
    };
    Graph.prototype.buildCenterLinesBetweenNodes = function () {
        var _this = this;
        this.eachRectanglePair(function (a, b) {
            if (a.top > b.bottom && a.top - b.bottom > _this.centerLineMinimalRequiredWidth) {
                _this.buildSingleCenterLine(a, b, (a.top + b.bottom) / 2, a.topSide, b.bottomSide, a.top, b.bottom);
            }
            if (b.top > a.bottom && b.top - a.bottom > _this.centerLineMinimalRequiredWidth) {
                _this.buildSingleCenterLine(a, b, (b.top + a.bottom) / 2, b.topSide, a.bottomSide, b.top, a.bottom);
            }
            if (a.left > b.right && a.left - b.right > _this.centerLineMinimalRequiredWidth) {
                _this.buildSingleCenterLine(a, b, (a.left + b.right) / 2, a.leftSide, b.rightSide, a.left, b.right);
            }
            if (b.left > a.right && b.left - a.right > _this.centerLineMinimalRequiredWidth) {
                _this.buildSingleCenterLine(a, b, (b.left + a.right) / 2, b.leftSide, a.rightSide, b.left, a.right);
            }
        });
    };
    Graph.prototype.buildSingleCenterLine = function (aRect, bRect, coordinate, a, b, min, max) {
        var aVector = new Vector2d(a.center.x, a.center.y, a.a.sub(a.b).rot270().unitVector);
        var bVector = new Vector2d(b.center.x, b.center.y, b.a.sub(b.b).rot90().unitVector);
        var crossRect = new Rectangle(Math.min(a.center.x, b.center.x), Math.min(a.center.y, b.center.y), 1, 1);
        crossRect.right = Math.max(a.center.x, b.center.x);
        crossRect.bottom = Math.max(a.center.y, b.center.y);
        /*aRect.draw();
         bRect.draw();
         crossRect.draw();*/
        if (this.rectangleIntersectsAnyRectangle(crossRect)) {
            return;
        }
        if (aVector.direction.x == 0) {
            var crossLine = new Line2d(0, coordinate);
        }
        else {
            var crossLine = new Line2d(Infinity, coordinate);
        }
        var intersectionA = crossLine.intersection(aVector.line);
        var intersectionB = crossLine.intersection(bVector.line);
        var vector1 = new Vector2d(intersectionA.x, intersectionA.y, aVector.direction.rot90()), vector2 = new Vector2d(intersectionB.x, intersectionB.y, bVector.direction.rot90());
        var closestRectCrossPoint1 = this.findClosestRectCross(vector1, null), closestRectCrossPoint2 = this.findClosestRectCross(vector2, null);
        this.baseAxises.push(new BaseAxis(closestRectCrossPoint1, closestRectCrossPoint2, this, GraphConstant.centerAxisCostMultiplier, new LeftSimpleConstraint(min), new RightSimpleConstraint(max), new CenterLocationDirective()));
    };
    Graph.prototype.buildNodes = function () {
        var node, newAxis;
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis = this.horizontalAxises[i];
            for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
                var vAxis = this.verticalAxises[j];
                var crossPoint = hAxis.getCrossPoint(vAxis);
                if (crossPoint) {
                    node = this.getNodeAt(crossPoint);
                    hAxis.addNode(node);
                    vAxis.addNode(node);
                    node.hAxis = hAxis;
                    node.vAxis = vAxis;
                    node.stale = true;
                }
            }
        }
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis = this.horizontalAxises[i];
            node = this.getNodeAt(hAxis.a);
            if (!node.stale) {
                newAxis = new BaseAxis(hAxis.a, hAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = true;
                this.verticalAxises.push(newAxis);
                hAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = hAxis;
                node.vAxis = newAxis;
            }
            node = this.getNodeAt(hAxis.b);
            if (!node.stale) {
                newAxis = new BaseAxis(hAxis.b, hAxis.b, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = true;
                this.verticalAxises.push(newAxis);
                hAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = hAxis;
                node.vAxis = newAxis;
            }
        }
        for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
            var vAxis = this.verticalAxises[j];
            node = this.getNodeAt(vAxis.a);
            if (!node.stale) {
                newAxis = new BaseAxis(vAxis.a, vAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = false;
                this.horizontalAxises.push(newAxis);
                vAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = newAxis;
                node.vAxis = vAxis;
            }
            node = this.getNodeAt(vAxis.b);
            if (!node.stale) {
                newAxis = new BaseAxis(vAxis.b, vAxis.b, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = false;
                this.horizontalAxises.push(newAxis);
                vAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = newAxis;
                node.vAxis = vAxis;
            }
        }
    };
    Graph.prototype.buildMergeRequests = function () {
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis = this.horizontalAxises[i];
            for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
                var vAxis = this.verticalAxises[j];
                var crossPoint = hAxis.getCrossPoint(vAxis);
                if (crossPoint) {
                    var node = this.getNodeAt(crossPoint);
                    if (node.stale) {
                        if (node.hAxis !== hAxis) {
                            this.addMergeRequest(node.hAxis, hAxis);
                        }
                        if (node.vAxis !== vAxis) {
                            this.addMergeRequest(node.vAxis, vAxis);
                        }
                    }
                    node.hAxis = hAxis;
                    node.vAxis = vAxis;
                    node.stale = true;
                }
            }
        }
    };
    Graph.prototype.addMergeRequest = function (a, b) {
        var foundAQueue, foundBQueue;
        for (var i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
            var queue = this.mergeAxisesQueue[i];
            if (queue.indexOf(a) !== -1) {
                foundAQueue = queue;
                break;
            }
        }
        for (var i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
            var queue = this.mergeAxisesQueue[i];
            if (queue.indexOf(b) !== -1) {
                foundBQueue = queue;
                break;
            }
        }
        if (foundAQueue !== undefined && foundAQueue === foundBQueue) {
            return;
        }
        if (!foundAQueue) {
            if (foundBQueue) {
                foundBQueue.push(a);
            }
            else {
                this.mergeAxisesQueue.push([a, b]);
            }
        }
        else {
            if (foundBQueue) {
                // must merge
                foundAQueue.push.apply(foundAQueue, foundBQueue);
                this.mergeAxisesQueue.splice(this.mergeAxisesQueue.indexOf(foundBQueue), 1);
            }
            else {
                foundAQueue.push(b);
            }
        }
    };
    Graph.prototype.getNodeAt = function (point) {
        var node = this.nodes[point.id];
        if (!node) {
            node = new NodePoint(point.x, point.y);
            this.nodes[point.id] = node;
        }
        return node;
    };
    Graph.prototype.findClosestRectCross = function (vector, ignoreRect) {
        var closestDistance = Infinity, closestPoint = null;
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect = this.rectangles[i];
            if (rect === ignoreRect) {
                continue;
            }
            var crossPoint = vector.getCrossPointWithRect(rect);
            if (crossPoint && closestDistance > crossPoint.distanceTo(vector.start)) {
                closestPoint = crossPoint;
                closestDistance = crossPoint.distanceTo(vector.start);
            }
        }
        if (closestDistance == Infinity) {
            this.outerRect.eachSide(function (side) {
                var crossPoint;
                if (crossPoint = vector.getCrossPointWithInterval(side)) {
                    if (vector.start.distanceTo(crossPoint) < closestDistance) {
                        closestPoint = crossPoint;
                        closestDistance = vector.start.distanceTo(crossPoint);
                    }
                }
            });
        }
        if (closestDistance == Infinity) {
            return vector.start;
        }
        return closestPoint;
    };
    Graph.prototype.rectangleIntersectsAnyRectangle = function (rectangle, ignoreRect) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            if (this.rectangles[i] === ignoreRect) {
                continue;
            }
            if (rectangle.intersection(this.rectangles[i]).isValid) {
                return true;
            }
        }
        return false;
    };
    Graph.prototype.intervalIntersectsAnyRectangle = function (interval, ignoreRect) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            if (this.rectangles[i] === ignoreRect) {
                continue;
            }
            if (interval.crossesRect(this.rectangles[i])) {
                return true;
            }
        }
        return false;
    };
    Graph.prototype.updateWithPath = function (path) {
        var connections = path.allConnections, axises = [];
        for (var i = 0; i < connections.length; i++) {
            var conn = connections[i];
            if (axises.indexOf(conn.axis) === -1) {
                axises.push(conn.axis);
                conn.axis.ensureTraversableSiblings();
                conn.axis.used = true;
                conn.axis.costMultiplier *= GraphConstant.usedAxisCostMultiplier;
            }
        }
        var nextNode;
        var current;
        var midNode;
        var next;
        var startNode;
        for (var i = 0; i < connections.length - 2; i++) {
            current = connections[i];
            next = connections[i + 1];
            startNode = current.a === next.a || current.a === next.b ? current.b : current.a;
            startNode.used = true;
            // startNode.draw("yellow");
            midNode = current.a === next.a || current.a === next.b ? current.a : current.b;
            nextNode = startNode;
            // connection can be divided before, traverse all nodes
            do {
                nextNode = nextNode.nextNode(current.directionFrom(startNode));
                nextNode.used = true;
            } while (nextNode !== midNode);
            midNode.used = true;
            // console.log(midNode.uid);
            if (current.vector.id !== next.vector.id) {
                // corner
                // all connections are used on corner
                // this will avoid double corner use
                midNode.eachConnection(function (conn) { return conn.traversable = false; });
            }
        }
        nextNode = startNode = midNode;
        current = next;
        midNode = current.a == nextNode ? current.b : current.a;
        do {
            nextNode = nextNode.nextNode(current.directionFrom(startNode));
            nextNode.used = true;
        } while (nextNode !== midNode);
        path.toNode.used = true;
        // path.toNode.draw("yellow");
        this.relocateAxises();
    };
    Graph.prototype.selfCheck = function () {
        var i;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].allClones.forEach(function (axis) { return axis.selfCheck(); });
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].allClones.forEach(function (axis) { return axis.selfCheck(); });
        }
    };
    Graph.prototype.relocateAxises = function () {
        var i, j;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            var axis = this.verticalAxises[i], clones = axis.allClones, usage = clones.map(function (axis) { return axis.used; });
            // console.log(clones.length);
            // console.log(usage);
            if (DEBUG_ENABLED) {
                if (usage[0] || usage[usage.length - 1] || (usage.length > 1 && !usage[1]) || (usage.length > 3 && !usage[3])) {
                    debugger;
                }
            }
            axis.linesIncluded = Math.floor(clones.length / 2);
            if (axis.linesIncluded > 0) {
                var current = 0;
                for (j = 0; j < clones.length; j++) {
                    var clone = clones[j];
                    clone.recommendedPosition = axis.locationDirective.getRecommendedPosition(current);
                    current += 0.5;
                }
            }
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var axis = this.horizontalAxises[i], clones = axis.allClones, usage = clones.map(function (axis) { return axis.used; });
            if (DEBUG_ENABLED) {
                if (usage[0] || usage[usage.length - 1] || (usage.length > 1 && !usage[1]) || (usage.length > 3 && !usage[3])) {
                    debugger;
                }
            }
            axis.linesIncluded = Math.floor(clones.length / 2);
            if (axis.linesIncluded > 0) {
                var current = 0;
                for (j = 0; j < clones.length; j++) {
                    var clone = clones[j];
                    clone.recommendedPosition = axis.locationDirective.getRecommendedPosition(current);
                    current += 0.5;
                }
            }
        }
        /*
         var leftConstraints: number[] = [];
         var rightConstraints: number[] = [];

         // sum left constraints
         for (i = 0; i < this.verticalAxises.length; i++) {
         var axis = this.verticalAxises[i];
         var atLeft = this.axisesConnectedAtLeft[axis.uid];
         var minConstraint = axis.leftConstraint.recommendedStart;
         if (atLeft) {
         for (j = 0; j < atLeft.length; j++) {
         var leftAxis = atLeft[j];
         if (!minConstraint || leftAxis.leftConstraint.recommendedEnd > minConstraint) {
         minConstraint = leftAxis.leftConstraint.recommendedEnd;
         }
         }
         }
         leftConstraints[axis.uid] = minConstraint;
         }
         console.log(leftConstraints);
         // sum right constraints
         for (i = this.verticalAxises.length - 1; i >= 0; i--) {
         var axis = this.verticalAxises[i];
         var atRight = this.axisesConnectedAtLeft[axis.uid];
         var minConstraint = axis.rightConstraint.recommendedStart;
         if (atRight) {
         for (j = 0; j < atRight.length; j++) {
         var rightAxis = atRight[j];
         if (!minConstraint || rightAxis.rightConstraint.recommendedEnd > minConstraint) {
         minConstraint = rightAxis.rightConstraint.recommendedEnd;
         }
         }
         }
         rightConstraints[axis.uid] = minConstraint;
         }

         // find intersected constraints
         for (i = this.verticalAxises.length - 1; i >= 0; i--) {
         //if ()
         }
         */
    };
    Graph.prototype.isConnectionUnderRect = function (interval) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect = this.rectangles[i];
            if (rect.containsPoint(interval.a) || rect.containsPoint(interval.b)) {
                return true;
            }
        }
        return false;
    };
    return Graph;
})();
var Path = (function () {
    function Path(connection, fromNode, previous) {
        this.connection = connection;
        this.previous = previous;
        this.fromNode = fromNode;
        this.cost = (this.previous ? this.previous.cost : 0) + this.connection.cost;
        if (this.previous && this.connection.directionFrom(this.fromNode).id !== this.previous.connection.directionFrom(this.previous.fromNode).id) {
            this.cost += GraphConstant.cornerCost;
        }
    }
    Object.defineProperty(Path.prototype, "uid", {
        get: function () {
            var vectorId = this.connection.a === this.fromNode ? this.connection.vector.id : this.connection.vector.rot180().id;
            return this.fromNode.uid * 10 + shortDirectionUid[vectorId];
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, "toNode", {
        get: function () {
            return this.connection.second(this.fromNode);
        },
        enumerable: true,
        configurable: true
    });
    Path.prototype.eachAvailableStep = function (fn) {
        var _this = this;
        var toNode = this.toNode;
        toNode.eachTraversableConnection(this.connection, function (to, conn) {
            fn(new Path(conn, toNode, _this));
        });
    };
    Path.prototype.canJoinWith = function (path) {
        return this.connection == path.connection && this.toNode == path.fromNode;
    };
    Path.prototype.draw = function (color) {
        if (color === void 0) { color = "red"; }
        this.connection.draw(color);
        if (this.previous) {
            this.previous.draw(color);
        }
    };
    Object.defineProperty(Path.prototype, "allConnections", {
        get: function () {
            if (this.previous) {
                var result = this.previous.allConnections;
                result.push(this.connection);
                return result;
            }
            return [this.connection];
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, "allNodes", {
        get: function () {
            if (this.previous) {
                var result = this.previous.allNodes;
                result.push(this.toNode);
                return result;
            }
            return [this.fromNode, this.toNode];
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, "points", {
        get: function () {
            var points = [], current = this, currentAxis = this.connection.axis;
            points.push(this.toNode.recommendedPoint);
            while (current) {
                if (current.connection.axis !== currentAxis) {
                    points.push(current.toNode.recommendedPoint);
                    currentAxis = current.connection.axis;
                }
                if (!current.previous) {
                    points.push(current.fromNode.recommendedPoint);
                }
                current = current.previous;
            }
            return points;
        },
        enumerable: true,
        configurable: true
    });
    Path.prototype.getSiblings = function () {
        if (this.previous) {
            throw new Error("Unable to get path siblings");
        }
        var result = [this], connectionDirection = this.connection.directionFrom(this.fromNode), direction = connectionDirection.rot90().abs(), oppositeDirection = direction.rot180(), nextNode, nextConnection;
        nextNode = this.fromNode;
        while (nextNode = nextNode.nextNode(direction)) {
            if (nextNode.x !== this.fromNode.x || nextNode.y !== this.fromNode.y) {
                break;
            }
            nextConnection = nextNode.connections[connectionDirection.id];
            if (nextConnection) {
                if (DEBUG_ENABLED) {
                    if (!(nextConnection.a === nextNode || nextConnection.b === nextNode)) {
                        debugger;
                    }
                    if (nextConnection.second(nextNode).x !== this.toNode.x || nextConnection.second(nextNode).y !== this.toNode.y) {
                        debugger;
                    }
                }
                result.push(new Path(nextConnection, nextNode, null));
            }
        }
        nextNode = this.fromNode;
        while (nextNode = nextNode.nextNode(oppositeDirection)) {
            if (nextNode.x !== this.fromNode.x || nextNode.y !== this.fromNode.y) {
                break;
            }
            nextConnection = nextNode.connections[connectionDirection.id];
            if (nextConnection) {
                if (DEBUG_ENABLED) {
                    if (!(nextConnection.a === nextNode || nextConnection.b === nextNode)) {
                        debugger;
                    }
                    if (nextConnection.second(nextNode).x !== this.toNode.x || nextConnection.second(nextNode).y !== this.toNode.y) {
                        debugger;
                    }
                }
                result.unshift(new Path(nextConnection, nextNode, null));
            }
        }
        if (DEBUG_ENABLED) {
            result.forEach(function (path) { return path.draw("violet"); });
        }
        return result;
    };
    return Path;
})();
var Finder = (function () {
    function Finder() {
        this.from = [];
        this.to = [];
    }
    Finder.prototype.addFrom = function (path) {
        var siblings = path.getSiblings();
        for (var i = siblings.length - 1; i >= 0; i--) {
            this.internalAddFrom(siblings[i]);
        }
    };
    Finder.prototype.binarySearch = function (heuristic) {
        var from = -1, to = this.from.length;
        while (to - from > 1) {
            var mid = Math.floor((from + to) / 2);
            if (this.from[mid].heuristic > heuristic) {
                to = mid;
            }
            else {
                from = mid;
            }
        }
        return from;
    };
    Finder.prototype.internalAddFrom = function (path) {
        path.heuristic = this.getHeuristic(path);
        // console.log(path.heuristic);
        var index = this.binarySearch(path.heuristic);
        if (DEBUG_ENABLED) {
            path.toNode.drawText("" + Math.round(path.heuristic));
        }
        this.from.splice(index + 1, 0, path);
    };
    Finder.prototype.addTo = function (path) {
        this.to.push.apply(this.to, path.getSiblings());
    };
    Finder.prototype.find = function () {
        var _this = this;
        var result, current, operationsCount = 0, from = this.from, to = this.to, closedNodes = [];
        this.filterNonTraversablePathes(from);
        this.filterNonTraversablePathes(to);
        while (current = from.shift()) {
            if (closedNodes[current.uid]) {
                continue;
            }
            for (var i = 0; i < to.length; i++) {
                if (current.canJoinWith(to[i])) {
                    result = current;
                }
            }
            if (result) {
                break;
            }
            if (operationsCount > 10000) {
                throw new Error("Maximum iterations limit reached" + current.heuristic);
            }
            operationsCount++;
            if (DEBUG_ENABLED) {
                current.connection.draw("blue");
                current.toNode.draw("blue");
            }
            closedNodes[current.uid] = true;
            current.eachAvailableStep(function (path) {
                if (closedNodes[path.uid]) {
                    return;
                }
                if (DEBUG_ENABLED) {
                    path.connection.draw();
                    if (isNaN(path.uid)) {
                        debugger;
                    }
                }
                _this.internalAddFrom(path);
            });
        }
        this.operationsCount = operationsCount;
        if (DEBUG_ENABLED) {
            console.log("Search took " + operationsCount + " operations, len = " + result.cost);
            result.draw();
        }
        return result;
    };
    Finder.prototype.filterNonTraversablePathes = function (pathes) {
        for (var i = pathes.length - 1; i >= 0; i--) {
            var current = pathes[i];
            if (current.connection.a.used && current.connection.b.used) {
                pathes.splice(i, 1);
                i--;
            }
        }
    };
    Finder.prototype.getHeuristic = function (newPath) {
        function weakCompare(point1, point2) {
            return (point1.x === point2.x && point2.x != 0) || (point1.y === point2.y && point2.y != 0);
        }
        var anglesCount;
        var distance = this.to[0].toNode.simpleDistanceTo(newPath.toNode);
        var sub = 0;
        for (var i = this.to.length - 1; i >= 0; i--) {
            if (newPath.canJoinWith(this.to[i])) {
                sub = this.to[i].cost;
                distance = 0;
                anglesCount = 0;
            }
        }
        if (anglesCount === undefined) {
            var newFrom = newPath.toNode;
            var to = this.to[0].toNode;
            var midNodesDirection = to.sub(newFrom);
            if (Math.abs(midNodesDirection.x) < 0.00001) {
                midNodesDirection.x = 0;
            }
            if (Math.abs(midNodesDirection.y) < 0.00001) {
                midNodesDirection.y = 0;
            }
            midNodesDirection.x = Math.sign(midNodesDirection.x);
            midNodesDirection.y = Math.sign(midNodesDirection.y);
            var newDirection = newPath.connection.directionFrom(newPath.fromNode);
            var toDirection = this.to[0].connection.directionFrom(to);
            var nextVector;
            if (newDirection.id == toDirection.id) {
                // 3 possible cases
                if ((newDirection.x == 0 && (midNodesDirection.y == 0 || midNodesDirection.y == newDirection.y)) ||
                    newDirection.y == 0 && (midNodesDirection.x == 0 || midNodesDirection.x == newDirection.x)) {
                    anglesCount = ((newDirection.x == 0 && midNodesDirection.x == 0) || (newDirection.y == 0 && midNodesDirection.y == 0)) ? 0 : 2;
                }
                else {
                    anglesCount = 4;
                }
            }
            else if (newDirection.id == toDirection.rot180().id) {
                // 2 possible cases
                if ((newDirection.x == 0 && midNodesDirection.x == 0) || (newDirection.y == 0 && midNodesDirection.y == 0)) {
                    anglesCount = 4;
                }
                else {
                    anglesCount = 2;
                }
            }
            else {
                // 2 possible cases
                if ((newDirection.x == 0 && (midNodesDirection.y == 0 || (newDirection.y == midNodesDirection.y && (toDirection.x == midNodesDirection.x || midNodesDirection.x == 0)))) ||
                    (newDirection.y == 0 && (midNodesDirection.x == 0 || (newDirection.x == midNodesDirection.x && (toDirection.y == midNodesDirection.y || midNodesDirection.y == 0))))) {
                    anglesCount = 1;
                }
                else {
                    anglesCount = 3;
                }
            }
        }
        // console.log(newDirection, toDirection, midNodesDirection, anglesCount);
        return newPath.cost + distance + distance * 0.00000001 - sub + anglesCount * GraphConstant.optimizationCornerCost;
    };
    return Finder;
})();
//# sourceMappingURL=Graph.js.map

define({});
/*jslint ignore:end*/
