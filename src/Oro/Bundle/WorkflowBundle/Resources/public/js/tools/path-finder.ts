'use strict';

var DEBUG_ENABLED = false;

class GraphConstant {
    static recommendedConnectionWidth: number = 12;
    static cornerCost: number = 200;
    static optimizationCornerCost: number = 190; // recomended > cornerCost - 1000 * usedAxisCostMultiplier;
    static crossPathCost: number = 300;  // recomended > cornerCost * 1.5
    // static turnPreference: number = -1; // right turn
    static centerAxisCostMultiplier: number = 0.99;
    static usedAxisCostMultiplier: number = 0.99;
    static overBlockLineCostMultiplier: number = 20;
}

interface IPoint2d {
    x: number;
    y: number
}

class Point2d implements IPoint2d{
    x: number;
    y: number;

    get id(): number {
        return this.x * 200000 + this.y;
    }

    static uidCounter = 0;
    _uid: number;

    get uid(): number {
        /*if (!this._uid) {
         this._uid = Point2d.uidCounter++;
         }*/
        return this._uid;
    }

    constructor(x: number, y: number) {
        this.x = x;
        this.y = y;
        this._uid = Point2d.uidCounter++;
    }

    simpleDistanceTo(point: Point2d) {
        var dx = point.x - this.x,
            dy = point.y - this.y;
        return Math.abs(dx) + Math.abs(dy);
    }

    distanceTo(point: Point2d) {
        var dx = point.x - this.x,
            dy = point.y - this.y;
        return Math.sqrt(dx * dx + dy * dy);
    }

    opposite(): Point2d {
        return new Point2d(-this.x, -this.y);
    }

    add(point: Point2d) {
        return new Point2d(this.x + point.x, this.y + point.y);
    }

    sub(point: Point2d) {
        return new Point2d(this.x - point.x, this.y - point.y);
    }

    mul(n: number) {
        return new Point2d(this.x * n, this.y * n);
    }

    get length() {
        return Math.sqrt(this.x * this.x + this.y * this.y);
    }

    get unitVector() {
        var len = this.length;
        return new Point2d(this.x / len, this.y / len);
    }

    draw(color: string ="red", radius: number = 2) {
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><circle fill="' + color + '" r="' + radius + '" ' +
            'cx="' + this.x + '" cy="' + this.y+ '"></circle></svg>')
    }

    drawText(text: string, color: string ="black") {
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><text x="' + (this.x + 5) + '" y="' + (this.y - 5) + '" fill="' + color + '" font-size="10">' + text + '</text></svg>')
    }

    rot90() : Point2d {
        return new Point2d(this.y, -this.x);
    }
    rot180() : Point2d {
        return new Point2d(-this.x, -this.y);
    }
    rot270() : Point2d {
        return new Point2d(-this.y, this.x);
    }
    abs() : Point2d {
        return new Point2d(Math.abs(this.x), Math.abs(this.y));
    }
    clone() : Point2d {
        return new Point2d(this.x, this.y);
    }
}

var Direction2d = {
    LEFT_TO_RIGHT: new Point2d(1, 0),
    RIGHT_TO_LEFT: new Point2d(-1, 0),
    TOP_TO_BOTTOM: new Point2d(0, 1),
    BOTTOM_TO_TOP: new Point2d(0, -1)
};

var Direction2dIds : number[] = [
    Direction2d.BOTTOM_TO_TOP.id,
    Direction2d.TOP_TO_BOTTOM.id,
    Direction2d.LEFT_TO_RIGHT.id,
    Direction2d.RIGHT_TO_LEFT.id
];

var shortDirectionUid = {};
for (var i = Direction2dIds.length - 1; i >= 0; i--) {
    shortDirectionUid[Direction2dIds[i]] = i;
}

interface Siblings<T> {
    first: T;
    second: T;
}

var util = {
    xor: function (a: boolean, b: boolean): boolean {
        return a ? !b : b;
    },
    between: function (b, a, c) {
        return (a <= b && b <= c) || (a >= b && b >= c);
    },
    betweenNonInclusive: function (b, a, c) {
        return (a < b && b < c) || (a > b && b > c);
    }
};

class UUID {
    static counter = 0;
    id: number;
    constructor() {
        this.id = UUID.counter++;
    }
}

class Interval1d {
    min: number;
    max: number;

    constructor(min: number, max: number) {
        this.min = min;
        this.max = max;
    }

    get width(): number {
        return this.max - this.min;
    }

    set width(v: number) {
        this.max = v - this.min;
    }

    get isValid(): boolean {
        return this.max > this.min;
    }

    intersection(s: Interval1d): Interval1d {
        return new Interval1d(
            Math.max(this.min, s.min),
            Math.min(this.max, s.max)
        );
    }

    union(s: Interval1d): Interval1d {
        return new Interval1d(
            Math.min(this.min, s.min),
            Math.max(this.max, s.max)
        );
    }

    contains(coordinate: number) {
        return (coordinate < this.min) ? false : coordinate <= this.max;
    }


    containsNonInclusive(coordinate: number) {
        return (coordinate <= this.min) ? false : coordinate < this.max;
    }

    distanceTo(coordinate: number): number {
        return (coordinate < this.min) ? this.min - coordinate : (coordinate > this.max ? coordinate - this.max : 0);
    }
}

class Interval2d {
    a: Point2d;
    b: Point2d;
    constructor(a: Point2d, b: Point2d) {
        this.a = a;
        this.b = b;
    }

    get length() {
        return this.a.distanceTo(this.b);
    }

    get simpleLength() {
        return this.a.simpleDistanceTo(this.b);
    }

    crosses(interval: Interval2d): boolean {
        return this.getCrossPoint(interval) !== null;
    }

    getCrossPoint(interval: Interval2d): Point2d {
        if (interval.simpleLength == 0) {
            return this.includesPoint(interval.a) ? interval.a : null;
        } else if (this.simpleLength == 0) {
            return interval.includesPoint(this.a) ? this.a : null;
        }
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            var v1: boolean, v2: boolean;
            if (this.a.x !== this.b.x) {
                // compare by x
                v1 = util.between(point.x, this.a.x, this.b.x);
            } else {
                // compare by y
                v1 = util.between(point.y, this.a.y, this.b.y);
            }

            if (interval.a.x !== interval.b.x) {
                // compare by x
                v2 = util.between(point.x, interval.a.x, interval.b.x);
            } else {
                // compare by y
                v2 = util.between(point.y, interval.a.y, interval.b.y);
            }
            if (v1 && v2) {
                return point;
            }
        }
        return null;
    }

    includesPoint(point: Point2d) {
        var line = this.line;
        return line.slope == Infinity ? (point.x == this.a.x && util.between(point.y, this.a.y, this.b.y)) : (util.between(point.x, this.a.x, this.b.x) && point.y == line.intercept + point.x * line.slope);
    }

    crossesNonInclusive(interval: Interval2d): boolean {
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            if (this.a.x !== this.b.x) {
                // compare by x
                return util.betweenNonInclusive(point.x, this.a.x, this.b.x);
            } else {
                // compare by y
                return util.betweenNonInclusive(point.y, this.a.y, this.b.y);
            }
        }
        return false;
    }

    crossesRect(rect: Rectangle): boolean {
        return rect.topSide.crosses(this) ||
            rect.bottomSide.crosses(this) ||
            rect.leftSide.crosses(this) ||
            rect.rightSide.crosses(this);
    }

    get line(): Line2d {
        return new Vector2d(this.a.x, this.a.y, this.a.sub(this.b).unitVector).line;
    }

    get center(): Point2d {
        return this.a.add(this.b).mul(0.5);
    }

    draw(color: string = 'green') {
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + this.a.x + ' ' + this.a.y + ' L ' + this.b.x + ' ' + this.b.y
            + '"></path></svg>')

    }
}

class Rectangle {
    horizontalInterval: Interval1d;
    verticalInterval: Interval1d;
    cid: string;

    get left(): number {
        return this.horizontalInterval.min;
    }

    set left(value: number) {
        this.horizontalInterval.min = value;
    }

    get right(): number {
        return this.horizontalInterval.max;
    }

    set right(value: number) {
        this.horizontalInterval.max = value;
    }

    get top(): number {
        return this.verticalInterval.min;
    }

    set top(value: number) {
        this.verticalInterval.min = value;
    }

    get bottom(): number {
        return this.verticalInterval.max;
    }

    set bottom(value: number) {
        this.verticalInterval.max = value;
    }

    get width(): number {
        return this.horizontalInterval.width;
    }

    set width(value: number) {
        this.horizontalInterval.width = value;
    }

    get height(): number {
        return this.verticalInterval.width;
    }

    set height(value: number) {
        this.verticalInterval.width = value;
    }

    get center(): Point2d {
        return new Point2d((this.left + this.right) / 2, (this.top + this.bottom) / 2);
    }

    constructor();
    constructor(horizontalStripe: Interval1d, verticalStripe: Interval1d);
    constructor(left: number, top: number, width: number, height: number);
    constructor(left: any = 0, top: any = 0, width: number = 0, height: number = 0) {
        if (left instanceof Interval1d) {
            this.horizontalInterval = <Interval1d>left;
            this.verticalInterval = <Interval1d>top;
        } else {
            this.horizontalInterval = new Interval1d(left, left + width);
            this.verticalInterval = new Interval1d(top, top + height);
        }
    }

    clone(): Rectangle {
        return new Rectangle(this.left, this.top, this.width, this.height);
    }

    intersection(box: Rectangle): Rectangle {
        return new Rectangle(this.horizontalInterval.intersection(box.horizontalInterval), this.verticalInterval.intersection(box.verticalInterval));
    }

    union(box: Rectangle): Rectangle {
        return new Rectangle(this.horizontalInterval.union(box.horizontalInterval), this.verticalInterval.union(box.verticalInterval));
    }

    get isValid(): boolean {
        return this.horizontalInterval.isValid && this.verticalInterval.isValid;
    }

    relative(point: Point2d): Rectangle {
        return new Rectangle(this.left - point.x, this.top - point.y, this.width, this.height);
    }

    distanceToPoint(point: Point2d): number {
        var dx = this.horizontalInterval.distanceTo(point.x),
            dy = this.verticalInterval.distanceTo(point.y);
        return Math.sqrt(dx*dx + dy*dy);
    }

    get topSide(): Interval2d {
        return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.right, this.top));
    }

    get bottomSide(): Interval2d {
        return new Interval2d(new Point2d(this.left, this.bottom), new Point2d(this.right, this.bottom));
    }

    get leftSide(): Interval2d {
        return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.left, this.bottom));
    }

    get rightSide(): Interval2d {
        return new Interval2d(new Point2d(this.right, this.top), new Point2d(this.right, this.bottom));
    }

    eachSide(fn: (side: Interval2d) => void) {
        fn(this.leftSide);
        fn(this.bottomSide);
        fn(this.rightSide);
        fn(this.topSide);
    }

    draw(color: string = "violet") {
        this.eachSide((side)=>side.draw(color));
    }

    containsPoint(point: Point2d): boolean {
        return this.horizontalInterval.containsNonInclusive(point.x) && this.verticalInterval.containsNonInclusive(point.y);
    }
}

class Vector2d {
    start: Point2d;
    direction: Point2d;
    constructor(x: number, y: number, direction: Point2d) {
        this.start = new Point2d(x, y);
        this.direction = direction;
    }

    crosses(rect: Rectangle): boolean {
        return this.getCrossPointWithRect(rect) !== null;
    }

    get line(): Line2d {
        var slope = this.direction.y / this.direction.x;
        if (slope == Infinity || slope == -Infinity) {
            return new Line2d(Infinity, this.start.x);
        }
        return new Line2d(slope, this.start.y + this.start.x * slope);
    }

    getCrossPointWithRect(rect: Rectangle): Point2d {
        var crossPoint: Point2d = null;
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

    }

    getCrossPointWithInterval(interval: Interval2d): Point2d {
        var intersectionPoint: Point2d = this.line.intersection(interval.line);
        if (!isNaN(intersectionPoint.x) && Math.abs(intersectionPoint.x) !== Infinity) {
            var relativePoint: Point2d = intersectionPoint.sub(this.start);
            if ((Math.sign(relativePoint.x) == Math.sign(this.direction.x)) &&
                (Math.sign(relativePoint.y) == Math.sign(this.direction.y))) {

                if (interval.a.x !== interval.b.x) {
                    if (util.between(intersectionPoint.x, interval.a.x, interval.b.x)) {
                        return intersectionPoint;
                    }
                } else {
                    if (util.between(intersectionPoint.y, interval.a.y, interval.b.y)) {
                        return intersectionPoint;
                    }
                }
            }
        }
        return null;
    }

    draw(color: string = "rgba(0,0,0,0.7)") {
        this.start.draw(color, 3);
        var interval: Interval2d = new Interval2d(this.start, this.start.add(this.direction.unitVector.mul(100000)));
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + interval.a.x + ' ' + interval.a.y + ' L ' + interval.b.x + ' ' + interval.b.y
            + '"></path></svg>')
    }
}

class Line2d {
    // in most cases it is y-intercept, but if slope == Infinity - it keeps x-intercept
    intercept: number;
    slope: number;
    constructor(slope: number, intercept: number) {
        this.slope = slope;
        this.intercept = intercept;
    }
    intersection(line: Line2d): Point2d {
        if (this.slope === Infinity) {
            if (line.slope === Infinity) {
                return new Point2d(NaN, NaN);
            }
            return new Point2d(this.intercept, line.intercept + line.slope * this.intercept);
        }
        if (line.slope === Infinity) {
            return new Point2d(line.intercept, this.intercept + this.slope * line.intercept);
        }
        var x = (line.intercept - this.intercept) / (this.slope - line.slope), // solve for x-coordinate of intersection
            y = this.slope * x + this.intercept; // solce
        return new Point2d(x, y);
    }
    draw(color: string = "orange") {
        var interval: Interval2d;
        if (this.slope === Infinity) {
            interval = new Interval2d(new Point2d(this.intercept, -100000), new Point2d(this.intercept, 100000));
        } else {
            interval = new Interval2d(new Point2d(-100000, this.slope * -100000 + this.intercept), new Point2d(100000, this.slope * 100000 + this.intercept));
        }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute; width: 1000px; height: 1000px;"><path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + interval.a.x + ' ' + interval.a.y + ' L ' + interval.b.x + ' ' + interval.b.y
            + '"></path></svg>')
    }
}

class NodePoint extends Point2d {
    connections: Connection[] = [];
    stale: boolean = false;
    hAxis: Axis;
    vAxis: Axis;
    used: boolean = false;
    constructor(x: number, y: number) {
        super(x, y);
    }
    get recommendedX() {
        if (this.vAxis) {
            var recommendation = this.vAxis.recommendedPosition;
            if (recommendation !== undefined) {
                return recommendation;
            }
        }
        return this.x;
    }
    get recommendedY() {
        if (this.hAxis) {
            var recommendation = this.hAxis.recommendedPosition;
            if (recommendation !== undefined) {
                return recommendation;
            }
        }
        return this.y;
    }

    get recommendedPoint(): Point2d {
        return new Point2d(this.recommendedX, this.recommendedY);
    }

    connect(direction: Point2d, node: NodePoint) {
        if (this.connections[direction.id]) {
            this.connections[direction.id].remove()
        }
        if (node) {
            new Connection(this, node, direction);
        }
    }

    removeConnection(conn: Connection) {
        var index = this.connections.indexOf(conn);
        if (index !== -1) {
            this.connections[index] = null;
        }
    }

    eachConnection(fn: (conn: Connection) => void) {
        for (var i = 0; i < Direction2dIds.length; i++) {
            var conn = this.connections[Direction2dIds[i]];
            if (conn) {
                fn(conn);
            }
        }
    }

    eachTraversableConnection(from: Connection, fn: (to: NodePoint, conn: Connection) => void) {
        for (var i = 0; i < Direction2dIds.length; i++) {
            var conn = this.connections[Direction2dIds[i]];
            if (conn && conn !== from && conn.traversable) {
                fn(conn.second(this), conn);
            }
        }
    }
    clone() {
        var node = new NodePoint(this.x, this.y);
        node.vAxis = this.vAxis;
        node.hAxis = this.hAxis;
        return node;
    }
    nextNode(direction: Point2d) {
        var connection = this.connections[direction.id];
        return connection ? connection.second(this) : null;
    }

    draw(color?: string, radius?: number) {
        this.recommendedPoint.draw(color, radius);
    }
}


class CenterNodePoint extends NodePoint {

}

class Connection extends Interval2d {
    static uidCounter = 0;
    uid: number;
    a: NodePoint;
    b: NodePoint;
    vector: Point2d;
    costMultiplier: number = 1;
    traversable: boolean = true;
    constructor(a: NodePoint, b: NodePoint, vector: Point2d) {
        super(a, b);
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

    get cost(): number {
        return this.length * this.axis.costMultiplier * this.costMultiplier + (this.a.used || this.b.used ? GraphConstant.crossPathCost : 0);
    }

    get axis(): Axis {
        return this.a.vAxis === this.b.vAxis ? this.a.vAxis : this.a.hAxis;
    }

    get leftSibling(): Connection {
        var leftPoint = this.a.nextNode(this.vector.rot90());
        if (leftPoint && leftPoint.x == this.a.x && leftPoint.y == this.a.y) {
            return leftPoint.connections[this.directionFrom(this.a).id];
        }
        return null;
    }
    get rightSibling(): Connection {
        var rightPoint = this.a.nextNode(this.vector.rot270());
        if (rightPoint && rightPoint.x == this.a.x && rightPoint.y == this.a.y) {
            return rightPoint.connections[this.directionFrom(this.a).id];
        }
        return null;
    }

    remove() {
        this.a.removeConnection(this);
        this.b.removeConnection(this);
    }

    second(first: NodePoint): NodePoint {
        return (first === this.a) ? this.b : this.a;
    }

    directionFrom(first: NodePoint) {
        return this.b === first ? this.vector.rot180() : this.vector;
    }

    replaceNode(original, replacement) {
        if (this.a == original) {
            var vector = this.vector;
            replacement.connections[vector.id] = this;
            original.connections[vector.id] = null;
            this.a = replacement;
        } else {
            var vector = this.vector.rot180();
            replacement.connections[vector.id] = this;
            original.connections[vector.id] = null;
            this.b = replacement;
        }
    }
    draw(color: string = 'green') {
        (new Interval2d(new Point2d(this.a.recommendedX, this.a.recommendedY), new Point2d(this.b.recommendedX, this.b.recommendedY))).draw(color);
    }
}

class AbstractSimpleConstraint {
    axis: BaseAxis;
    recomendedStart: number = undefined;
    recommendedEnd: number;
}

class LeftSimpleConstraint extends AbstractSimpleConstraint{
    constructor(recomendedStart: number) {
        super();
        this.recomendedStart = recomendedStart;
    }
    get recommendedEnd(): number {
        return this.recomendedStart + this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
    }
}

class RightSimpleConstraint extends AbstractSimpleConstraint {
    constructor(recomendedStart: number) {
        super();
        this.recomendedStart = recomendedStart;
    }
    get recommendedBound(): number {
        return this.recomendedStart + this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
    }
}

class EmptyConstraint extends AbstractSimpleConstraint {
    get recommendedEnd(): number{
        return undefined;
    }
}

class AbstractLocationDirective {
    axis: BaseAxis;
    getRecommendedPosition(lineNo: number): number{
        throw new Error("That's abstract method")
    }
}

class CenterLocationDirective extends AbstractLocationDirective {
    getRecommendedPosition(lineNo: number): number{
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        return center - requiredWidth / 2 + GraphConstant.recommendedConnectionWidth * lineNo;
    }
}

class StickLeftLocationDirective extends AbstractLocationDirective {
    getRecommendedPosition(lineNo: number): number {
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = this.axis.linesIncluded * GraphConstant.recommendedConnectionWidth;
        return center + GraphConstant.recommendedConnectionWidth * (lineNo + 0.5);
    }
}

class StickRightLocationDirective extends AbstractLocationDirective {
    getRecommendedPosition(lineNo: number): number {
        var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
        var requiredWidth = (this.axis.linesIncluded + 0.5) * GraphConstant.recommendedConnectionWidth;
        return center - requiredWidth + GraphConstant.recommendedConnectionWidth * lineNo;
    }
}

class Axis extends Interval2d {
    static uidCounter : number = 0;
    uid: number;
    costMultiplier: number;
    graph: Graph;
    clonesAtLeft: Axis[] = [];
    clonesAtRight: Axis[] = [];
    used: boolean = false;
    recommendedPosition: number;
    isVertical: boolean;

    constructor(a: Point2d, b:Point2d, graph: Graph, costMultiplier: number = 1) {
        super(a, b);
        this.isVertical = this.a.x === this.b.x;
        this.uid = Axis.uidCounter++;
        this.costMultiplier = costMultiplier;
        this.graph = graph;
    }

    get connections() : Connection[] {
        var result: Connection[] = [];
        var vectorId = this.b.sub(this.a).unitVector.abs().rot180().id;
        for (var i = this.nodes.length - 1; i >= 1; i--) {
            var node = this.nodes[i];
            result.push(node.connections[vectorId]);
        }
        return result;
    }

    get closestLeftClone(): Axis {
        var closestLeftClone: Axis = null,
            leftClone: Axis = this.clonesAtLeft[0];
        while (leftClone) {
            closestLeftClone = leftClone;
            leftClone = leftClone.clonesAtRight[leftClone.clonesAtRight.length - 1];
        }
        return closestLeftClone;
    }

    get closestRightClone(): Axis {
        var closestRightClone: Axis = null,
            rightClone: Axis = this.clonesAtRight[0];
        while (rightClone) {
            closestRightClone = rightClone;
            rightClone = rightClone.clonesAtLeft[rightClone.clonesAtLeft.length - 1];
        }
        return closestRightClone;
    }

    nodes: NodePoint[] = [];

    get allClones() : Axis[] {
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
    }

    static createFromInterval(interval: Interval2d, graph: Graph) {
        var costMultiplier = (<Axis>interval).costMultiplier;
        var isVertical = (<Axis>interval).isVertical;
        var clone = new Axis(interval.a, interval.b, graph, costMultiplier);
        // this is fix for zero length axises
        if (isVertical !== undefined) {
            clone.isVertical = isVertical;
        }
        return clone;
    }

    addNode(node: NodePoint) {
        if (this.isVertical) {
            node.vAxis = this;
        } else {
            node.hAxis = this;
        }
        if (this.nodes.indexOf(node) !== -1) {
            return;
        }
        this.nodes.push(node);
    }

    get nextNodeConnVector(): Point2d {
        if (this.isVertical) {
            return Direction2d.TOP_TO_BOTTOM;
        } else {
            return Direction2d.LEFT_TO_RIGHT;
        }
    }

    get prevNodeConnVector(): Point2d {
        if (this.isVertical) {
            return Direction2d.BOTTOM_TO_TOP;
        } else {
            return Direction2d.RIGHT_TO_LEFT;
        }
    }

    addFinalNode(node: NodePoint) {
        var nextNodeConnVector = this.nextNodeConnVector,
            nextNodeConn,
            prevNodeConn;
        if (nextNodeConn = node.connections[nextNodeConnVector.id]) {
            var nextNode = nextNodeConn.second(node),
                nextIndex = this.nodes.indexOf(nextNode);
            if (nextIndex === -1)
                throw Error("Invalid add node call");
            this.nodes.splice(nextIndex, 0, node);
            if (this.nodes[nextIndex].connections[nextNodeConnVector.id].second(this.nodes[nextIndex]) !== nextNode) {
                debugger;
            }
            if (nextNode.connections[nextNodeConnVector.rot180().id].second(nextNode) !== this.nodes[nextIndex]) {
                debugger;
            }
        } else if (prevNodeConn = node.connections[this.prevNodeConnVector.id]) {
            var prevNode = prevNodeConn.second(node),
                prevIndex = this.nodes.indexOf(prevNode);
            if (prevIndex === -1)
                throw Error("Invalid add node call");
            this.nodes.splice(prevIndex + 1, 0, node);

            if (this.nodes[prevIndex].connections[nextNodeConnVector.id].second(this.nodes[prevIndex]) !== node) {
                debugger;
            }
            if (node.connections[nextNodeConnVector.rot180().id].second(node) !== this.nodes[prevIndex]) {
                debugger;
            }
        } else {
            throw Error("Node should be connected before addition");
        }
        if (DEBUG_ENABLED) {
            this.selfCheck();
        }
    }

    selfCheck() {
        var current = this.nodes[0];
        var prev = this.nodes[0];
        var nextNodeConnVector = this.b.sub(this.a).unitVector;
        var i = 0;
        while (i++, current = current.nextNode(nextNodeConnVector)) {
            if (current !== this.nodes[i]) {
                debugger;
                return false;
            }
            if (current.nextNode(nextNodeConnVector.rot180()) !== this.nodes[i-1]) {
                debugger;
                return false;
            }
            if (current.x + current.y < prev.x + prev.y) {
                debugger;
                return false;
            }
        }
        return true;
    }

    finalize() {
        // this.nodes.forEach((node)=>node.draw('red'));
        var firstNode = this.nodes[0];
        var lastNode = this.nodes[this.nodes.length - 1];
        if (this.isVertical) {
            firstNode.removeConnection(firstNode.connections[Direction2d.TOP_TO_BOTTOM.id]);
            lastNode.removeConnection(firstNode.connections[Direction2d.BOTTOM_TO_TOP.id]);
            for (var i = this.nodes.length - 1; i >= 0; i--) {
                var node = this.nodes[i];
                node.vAxis = this;
                node.connect(Direction2d.BOTTOM_TO_TOP, this.nodes[i-1]);
            }
        } else {
            firstNode.removeConnection(firstNode.connections[Direction2d.LEFT_TO_RIGHT.id]);
            lastNode.removeConnection(firstNode.connections[Direction2d.RIGHT_TO_LEFT.id]);
            for (var i = this.nodes.length - 1; i >= 0; i--) {
                var node = this.nodes[i];
                node.hAxis = this;
                node.connect(Direction2d.RIGHT_TO_LEFT, this.nodes[i-1]);
            }
        }
    }

    sortNodes() {
        // sort nodes and connect them
        var isVertical = this.isVertical;
        this.nodes.sort(function (a, b) {
            if (DEBUG_ENABLED) {
                if (a.x === b.x && a.y === b.y) {
                    // virtual node
                    debugger;
                }
            }
            return a.x - b.x + a.y - b.y;
        });
    }

    merge(axis: Axis) {
        var middle = this.a.add(this.b).mul(0.5);
        var points = [this.a, this.b, axis.a, axis.b];
        points.sort(function (a, b) {
            return a.x + a.y - b.x - b.y;
        });

        this.a = points[0];
        this.b = points[points.length - 1];
    }

    cloneAtDirection(direction: Point2d): Axis {
        var axis = Axis.createFromInterval(this, this.graph);
        for (var i = 0; i < this.nodes.length; i++) {
            var node = this.nodes[i],
                clonedNode = node.clone(),
                secondNode = node.nextNode(direction);

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
            } else {
                node.vAxis.addFinalNode(clonedNode);
            }
            axis.addNode(clonedNode);
        }
        axis.finalize();
        return axis;
    }

    ensureTraversableSiblings() {
        var clone;
        clone = this.closestLeftClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs().rot180());
            this.clonesAtLeft.unshift(this.cloneAtDirection(this.prevNodeConnVector.rot90()));
        }
        clone = this.closestRightClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs());
            this.clonesAtRight.unshift(this.cloneAtDirection(this.nextNodeConnVector.rot90()));
        }
    }
}

class BaseAxis extends Axis {
    linesIncluded: number = 1;
    leftConstraint: AbstractSimpleConstraint;
    rightConstraint: AbstractSimpleConstraint;
    locationDirective: AbstractLocationDirective;

    constructor(a: Point2d, b:Point2d, graph: Graph, costMultiplier: number, leftConstraint?: AbstractSimpleConstraint, rightConstraint?: AbstractSimpleConstraint, locationDirective?: AbstractLocationDirective) {
        super(a, b, graph, costMultiplier);
        leftConstraint.axis = this;
        rightConstraint.axis = this;
        locationDirective.axis = this;
        this.leftConstraint = leftConstraint;
        this.rightConstraint = rightConstraint;
        this.locationDirective = locationDirective;
    }

    static createFromInterval(interval: Interval2d, graph: Graph, leftConstraint?: AbstractSimpleConstraint, rightConstraint?: AbstractSimpleConstraint, locationDirective?: AbstractLocationDirective) {
        var costMultiplier = (<Axis>interval).costMultiplier;
        var isVertical = (<Axis>interval).isVertical;
        var clone = new BaseAxis(interval.a, interval.b, graph, costMultiplier, leftConstraint, rightConstraint, locationDirective);
        // this is fix for zero length axises
        if (isVertical !== undefined) {
            clone.isVertical = isVertical;
        }
        return clone;
    }
}

interface ICornerAxisSpec {
    vectorA: Vector2d;
    vectorB: Vector2d;
    leftConstraint: AbstractSimpleConstraint;
    rightConstraint: AbstractSimpleConstraint;
    locationDirective: AbstractLocationDirective;
}

interface ICenterAxisSpec {
    vector: Vector2d;
    leftConstraint: AbstractSimpleConstraint;
    rightConstraint: AbstractSimpleConstraint;
    locationDirective: AbstractLocationDirective;
}

class Graph {
    rectangles: Rectangle[] = [];
    baseAxises: BaseAxis[] = [];
    horizontalAxises: BaseAxis[] = [];
    verticalAxises: BaseAxis[] = [];
    nodes: {[key: number]: NodePoint} = {};
    mergeAxisesQueue: BaseAxis[][] = [];
    outerRect: Rectangle;
    build() {
        this.outerRect = this.rectangles.reduce(
            (prev: Rectangle, current: Rectangle) => current.union(prev),
            new Rectangle(this.rectangles[0].top,this.rectangles[0].left,0,0)
        );

        this.baseAxises.push(
            BaseAxis.createFromInterval(this.outerRect.topSide, this, new EmptyConstraint(), new RightSimpleConstraint(this.outerRect.top), new StickRightLocationDirective()),
            BaseAxis.createFromInterval(this.outerRect.bottomSide, this, new LeftSimpleConstraint(this.outerRect.bottom), new EmptyConstraint(), new StickLeftLocationDirective()),
            BaseAxis.createFromInterval(this.outerRect.leftSide, this, new EmptyConstraint(), new RightSimpleConstraint(this.outerRect.left), new StickRightLocationDirective()),
            BaseAxis.createFromInterval(this.outerRect.rightSide, this, new LeftSimpleConstraint(this.outerRect.right), new EmptyConstraint(), new StickLeftLocationDirective())
        );

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
    }

    getPathFromCid(cid: string, direction: Point2d) {
        return this.getPathFrom(this.getRectByCid(cid), direction);
    }

    getPathFrom(rect: Rectangle, direction: Point2d) {
        var center = rect.center,
            node;
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
    }

    getRectByCid(cid: string) {
        for (var i = 0; i < this.rectangles.length; i++) {
            var rect = this.rectangles[i];
            if (rect.cid === cid) {
                return rect;
            }
        }
        return null;
    }

    draw() {
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
    }

    createAxises() {
        for (var i = 0; i < this.baseAxises.length; i++) {
            var axis = this.baseAxises[i];
            if (axis.isVertical) {
                var newAxis = new BaseAxis(axis.a, axis.b, this, axis.costMultiplier, axis.leftConstraint, axis.rightConstraint, axis.locationDirective);
                newAxis.isVertical = axis.isVertical;
                this.verticalAxises.push(newAxis);
            } else if (axis.a.y === axis.b.y) {
                var newAxis = new BaseAxis(axis.a, axis.b, this, axis.costMultiplier, axis.leftConstraint, axis.rightConstraint, axis.locationDirective);
                newAxis.isVertical = axis.isVertical;
                this.horizontalAxises.push(newAxis);
            } else {
                throw Error("Not supported");
            }
        }
    }

    removeAxis(axis: BaseAxis) {
        var index;
        if ((index = this.horizontalAxises.indexOf(axis)) !== -1) {
            this.horizontalAxises.splice(index, 1);
            return;
        }
        if ((index = this.verticalAxises.indexOf(axis)) !== -1) {
            this.verticalAxises.splice(index, 1);
        }
    }

    mergeAxises() {
        var i, j;
        for (i = 0; i < this.mergeAxisesQueue.length; i++) {
            var queue = this.mergeAxisesQueue[i];
            if (DEBUG_ENABLED) {
                console.log(queue.map((axis)=>{axis.draw(); return axis.uid}));
            }
            for (j = queue.length - 1; j >= 1; j--) {
                queue[j - 1].merge(queue[j]);
                this.removeAxis(queue[j]);
            }
        }
    }

    axisesConnectedAtLeft: BaseAxis[][] = [];
    axisesConnectedAtRight: BaseAxis[][] = [];

    finalizeAxises() {
        var i;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].sortNodes();
            this.verticalAxises[i].finalize();
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].sortNodes();
            this.horizontalAxises[i].finalize();
        }
        this.verticalAxises.sort((a, b) => a.a.x - b.a.x);
        this.horizontalAxises.sort((a, b) => a.a.y - b.a.y);

        // find connections between axises
        for (i = 0; i < this.horizontalAxises.length; i++) {
            var axis = this.horizontalAxises[i];
            for (var j = axis.nodes.length - 1; j >= 0; j--) {
                var node = axis.nodes[j],
                    connectionToLeft = node.connections[Direction2d.TOP_TO_BOTTOM.id],
                    nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null,
                    connectionToRight = node.connections[Direction2d.BOTTOM_TO_TOP.id],
                    nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                if (nodeAtLeft) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, <BaseAxis>nodeAtLeft.hAxis);
                }
                if (nodeAtRight) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, <BaseAxis>nodeAtRight.hAxis);
                }
            }
        }

        for (i = 0; i < this.verticalAxises.length; i++) {
            var axis = this.verticalAxises[i];
            for (var j = axis.nodes.length - 1; j >= 0; j--) {
                var node = axis.nodes[j],
                    connectionToLeft = node.connections[Direction2d.RIGHT_TO_LEFT.id],
                    nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null,
                    connectionToRight = node.connections[Direction2d.LEFT_TO_RIGHT.id],
                    nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                if (nodeAtLeft) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, <BaseAxis>nodeAtLeft.vAxis);
                }
                if (nodeAtRight) {
                    this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, <BaseAxis>nodeAtRight.vAxis);
                }
            }
        }
    }

    addAxisConnectionInfo(keeper: BaseAxis[][], main: BaseAxis, secondary: BaseAxis) {
        if (!keeper[main.uid]) {
            keeper[main.uid] = [];
        }
        if (keeper[main.uid].indexOf(secondary) === -1) {
            keeper[main.uid].push(secondary);
        }
    }

    hasAxis(axis: BaseAxis): boolean {
        return this.horizontalAxises.indexOf(axis) !== -1 || this.verticalAxises.indexOf(axis) !== -1;
    }

    buildCornerAxises() {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect = this.rectangles[i];
            var defs: ICornerAxisSpec[] = [
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
                var def: ICornerAxisSpec = defs[j];
                var closestRectCrossPoint1 = this.findClosestRectCross(def.vectorA, rect),
                    closestRectCrossPoint2 = this.findClosestRectCross(def.vectorB, rect);
                this.baseAxises.push(BaseAxis.createFromInterval(new Interval2d(closestRectCrossPoint1, closestRectCrossPoint2), this, def.leftConstraint, def.rightConstraint, def.locationDirective));
            }
        }
    }

    buildCenterAxises() {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect: Rectangle = this.rectangles[i];
            var center: Point2d = rect.center;

            var defs: ICenterAxisSpec[] = [
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
                var def: ICenterAxisSpec = defs[j];
                var closestRectCrossPoint = this.findClosestRectCross(def.vector, rect);
                var axis = new BaseAxis(def.vector.start, closestRectCrossPoint, this, 1, def.leftConstraint, def.rightConstraint, def.locationDirective);
                var secondaryAxis = new BaseAxis(def.vector.start, def.vector.start, this, 1, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                secondaryAxis.isVertical = !axis.isVertical;
                // console.log(secondaryAxis.uid, axis.getCrossPoint(secondaryAxis), secondaryAxis.getCrossPoint(axis));
                this.baseAxises.push(axis, secondaryAxis);
            }
        }
    }

    eachRectanglePair(fn: (a:Rectangle, b:Rectangle) => void) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect1 = this.rectangles[i];
            for (var j = i - 1; j >= 0; j--) {
                fn(rect1, this.rectangles[j]);
            }
        }
    }

    centerLineMinimalRequiredWidth: number = 32;

    buildCenterLinesBetweenNodes() {
        this.eachRectanglePair((a:Rectangle, b:Rectangle) => {
            if (a.top > b.bottom && a.top - b.bottom > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (a.top + b.bottom)/2, a.topSide, b.bottomSide, a.top, b.bottom);
            }
            if (b.top > a.bottom && b.top - a.bottom > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (b.top + a.bottom)/2, b.topSide, a.bottomSide, b.top, a.bottom);
            }
            if (a.left > b.right && a.left - b.right > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (a.left + b.right)/2, a.leftSide, b.rightSide, a.left, b.right);
            }
            if (b.left > a.right && b.left - a.right > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (b.left + a.right)/2, b.leftSide, a.rightSide, b.left, a.right);
            }
        });
    }

    buildSingleCenterLine(aRect:Rectangle, bRect:Rectangle, coordinate: number, a: Interval2d, b: Interval2d, min: number, max: number) {
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
        } else {
            var crossLine = new Line2d(Infinity, coordinate);
        }
        var intersectionA = crossLine.intersection(aVector.line);
        var intersectionB = crossLine.intersection(bVector.line);

        var vector1 = new Vector2d(intersectionA.x, intersectionA.y, aVector.direction.rot90()),
            vector2 = new Vector2d(intersectionB.x, intersectionB.y, bVector.direction.rot90());
        var closestRectCrossPoint1 = this.findClosestRectCross(vector1, null),
            closestRectCrossPoint2 = this.findClosestRectCross(vector2, null);
        this.baseAxises.push(new BaseAxis(
            closestRectCrossPoint1,
            closestRectCrossPoint2,
            this,
            GraphConstant.centerAxisCostMultiplier,
            new LeftSimpleConstraint(min),
            new RightSimpleConstraint(max),
            new CenterLocationDirective()));
    }

    buildNodes() {
        /*
         * add all nodes at axises cross points
         */
        var node: NodePoint, newAxis: BaseAxis;
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis: Axis = this.horizontalAxises[i];
            for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
                var vAxis: Axis = this.verticalAxises[j];
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

        /*
         * add all nodes at axises end points
         */
        var newVerticalAxises: BaseAxis[] = [];
        var newHorizontalAxises: BaseAxis[] = [];
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis: Axis = this.horizontalAxises[i];
            node = this.getNodeAt(hAxis.a);
            if (!node.stale) {
                newAxis = new BaseAxis(hAxis.a, hAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = true;
                newVerticalAxises.push(newAxis);
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
            var vAxis: Axis = this.verticalAxises[j];
            node = this.getNodeAt(vAxis.a);
            if (!node.stale) {
                newAxis = new BaseAxis(vAxis.a, vAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(), new CenterLocationDirective());
                newAxis.isVertical = false;
                newHorizontalAxises.push(newAxis);
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
        this.verticalAxises.push.apply(this.verticalAxises, newVerticalAxises);
        this.horizontalAxises.push.apply(this.horizontalAxises, newHorizontalAxises);
    }

    buildMergeRequests() {
        for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
            var hAxis: BaseAxis = this.horizontalAxises[i];
            for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
                var vAxis: BaseAxis = this.verticalAxises[j];
                var crossPoint = hAxis.getCrossPoint(vAxis);
                if (crossPoint) {
                    var node: NodePoint = this.getNodeAt(crossPoint);
                    if (node.stale) {
                        if (node.hAxis !== hAxis) {
                            this.addMergeRequest(<BaseAxis>node.hAxis, hAxis);
                        }
                        if (node.vAxis !== vAxis) {
                            this.addMergeRequest(<BaseAxis>node.vAxis, vAxis);
                        }
                    }
                    node.hAxis = <BaseAxis>hAxis;
                    node.vAxis = <BaseAxis>vAxis;
                    node.stale = true;
                }
            }
        }
    }

    addMergeRequest(a: BaseAxis, b: BaseAxis) {
        var foundAQueue, foundBQueue;
        for (var i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
            var queue = this.mergeAxisesQueue[i];
            if (queue.indexOf(a)!== -1) {
                foundAQueue = queue;
                break;
            }
        }
        for (var i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
            var queue = this.mergeAxisesQueue[i];
            if (queue.indexOf(b)!== -1) {
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
            } else {
                this.mergeAxisesQueue.push([a,b]);
            }
        } else {
            if (foundBQueue) {
                // must merge
                foundAQueue.push.apply(foundAQueue, foundBQueue);
                this.mergeAxisesQueue.splice(this.mergeAxisesQueue.indexOf(foundBQueue), 1);
            } else {
                foundAQueue.push(b);
            }
        }
    }

    getNodeAt(point: Point2d): NodePoint {
        var node = this.nodes[point.id];
        if (!node) {
            node = new NodePoint(point.x, point.y);
            this.nodes[point.id] = node;
        }
        return node;
    }

    findClosestRectCross(vector: Vector2d, ignoreRect: Rectangle): Point2d {
        var closestDistance: number = Infinity,
            closestPoint: Point2d = null;
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
            this.outerRect.eachSide((side: Interval2d) => {
                var crossPoint: Point2d;
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
    }

    rectangleIntersectsAnyRectangle(rectangle: Rectangle, ignoreRect?: Rectangle) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            if (this.rectangles[i] === ignoreRect) {
                continue;
            }
            if (rectangle.intersection(this.rectangles[i]).isValid) {
                return true;
            }
        }
        return false;
    }

    intervalIntersectsAnyRectangle(interval: Interval2d, ignoreRect?: Rectangle) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            if (this.rectangles[i] === ignoreRect) {
                continue;
            }
            if (interval.crossesRect(this.rectangles[i])) {
                return true;
            }
        }
        return false;
    }

    updateWithPath(path: Path) {
        var connections: Connection[] = path.allConnections,
            axises: Axis[] = [];
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
        var markedNodes = [];
        for (var i = 0; i < connections.length - 2; i++) {
            current = connections[i];
            next = connections[i + 1];
            startNode = current.a === next.a || current.a === next.b ? current.b : current.a;
            midNode = current.a === next.a || current.a === next.b ? current.a : current.b;
            midNode.used = true;
            startNode.used = true;
            nextNode = startNode;
            // connection can be divided before, traverse all nodes
            do {
                nextNode = nextNode.nextNode(current.directionFrom(startNode));
                nextNode.used = true;
                // console.log(midNode.uid);
            } while (nextNode !== midNode);
            // console.log(midNode.uid);
            if (current.vector.id !== next.vector.id) {
                // corner
                // all connections are used on corner
                // this will avoid double corner use
                midNode.eachConnection((conn) => conn.traversable = false);
            }
        }
        nextNode = startNode = midNode;
        current = next;
        midNode = current.a == nextNode ? current.b : current.a;
        do {
            nextNode = nextNode.nextNode(current.directionFrom(startNode));
            nextNode.used = true;
            // console.log(midNode.uid);
        } while (nextNode !== midNode);

        path.toNode.used = true;
        markedNodes.push(path.toNode);

        this.relocateAxises();
    }

    selfCheck() {
        var i;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].allClones.forEach((axis)=>axis.selfCheck());
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].allClones.forEach((axis)=>axis.selfCheck());
        }
    }

    relocateAxises() {
        var i, j;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            var axis = this.verticalAxises[i],
                clones = axis.allClones,
                usage = clones.map((axis) => axis.used);
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
            var axis = this.horizontalAxises[i],
                clones = axis.allClones,
                usage = clones.map((axis)=>axis.used);
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
    }

    isConnectionUnderRect(interval: Interval2d) {
        for (var i = this.rectangles.length - 1; i >= 0; i--) {
            var rect: Rectangle = this.rectangles[i];
            if (rect.containsPoint(interval.a) || rect.containsPoint(interval.b)) {
                return true;
            }
        }
        return false;
    }
}

class Path {
    connection: Connection;
    fromNode: NodePoint;

    get uid(): number {
        var vectorId = this.connection.a === this.fromNode ? this.connection.vector.id : this.connection.vector.rot180().id;
        return this.fromNode.uid * 10 + shortDirectionUid[vectorId];
    }

    get toNode(): NodePoint {
        return this.connection.second(this.fromNode);
    }

    previous: Path;
    cost: number;
    heuristic: number;

    constructor(connection: Connection, fromNode: NodePoint, previous: Path) {
        this.connection = connection;
        this.previous = previous;
        this.fromNode = fromNode;
        this.cost = (this.previous ? this.previous.cost : 0) + this.connection.cost;
        if (this.previous && this.connection.directionFrom(this.fromNode).id !== this.previous.connection.directionFrom(this.previous.fromNode).id) {
            this.cost += GraphConstant.cornerCost;
        }
    }

    eachAvailableStep(fn: (path: Path) => void) {
        var toNode = this.toNode;
        toNode.eachTraversableConnection(this.connection, (to: NodePoint, conn: Connection) => {
            fn(new Path(conn, toNode, this));
        });
    }

    canJoinWith(path: Path) {
        return this.connection == path.connection && this.toNode == path.fromNode;
    }

    draw(color: string = "red") {
        this.connection.draw(color);
        if (this.previous) {
            this.previous.draw(color);
        }
    }

    get allConnections(): Connection[] {
        if (this.previous) {
            var result = this.previous.allConnections;
            result.push(this.connection);
            return result;
        }
        return [this.connection];
    }

    get allNodes(): NodePoint[] {
        if (this.previous) {
            var result = this.previous.allNodes;
            result.push(this.toNode);
            return result;
        }
        return [this.fromNode, this.toNode];
    }

    get points(): Point2d[] {
        var points: Point2d[] = [],
            current = this,
            currentAxis = this.connection.axis;
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
    }

    getSiblings() : Path[] {
        if (this.previous) {
            throw new Error("Unable to get path siblings");
        }
        var result: Path[] = [this],
            connectionDirection = this.connection.directionFrom(this.fromNode),
            direction = connectionDirection.rot90().abs(),
            oppositeDirection = direction.rot180(),
            nextNode: NodePoint,
            nextConnection: Connection;

        nextNode = this.fromNode;
        while (nextNode = nextNode.nextNode(direction)) {
            if (nextNode.x !== this.fromNode.x || nextNode.y !== this.fromNode.y) {
                break;
            }
            nextConnection = nextNode.connections[connectionDirection.id];
            if (nextConnection) {
                if (DEBUG_ENABLED) {
                    if ( !(nextConnection.a === nextNode || nextConnection.b === nextNode) ) {
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
                    if ( !(nextConnection.a === nextNode || nextConnection.b === nextNode) ) {
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
            result.forEach((path)=> path.draw("violet"));
        }

        return result;
    }
}

class Finder {
    from: Path[] = [];
    to: Path[] = [];
    operationsCount: number;

    addFrom(path: Path) {
        var siblings = path.getSiblings();
        for (var i = siblings.length - 1; i >= 0; i--) {
            this.internalAddFrom(siblings[i]);
        }
    }

    binarySearch(heuristic: number) {
        var from = -1, to = this.from.length;
        while (to - from > 1) {
            var mid = Math.floor((from + to) / 2);
            if (this.from[mid].heuristic > heuristic) {
                to = mid;
            } else {
                from = mid;
            }
        }
        return from;
    }

    internalAddFrom(path: Path) {
        path.heuristic = this.getHeuristic(path);
        // console.log(path.heuristic);
        var index = this.binarySearch(path.heuristic);
        if (DEBUG_ENABLED) {
            path.toNode.drawText("" + Math.round(path.heuristic));
        }
        this.from.splice(index + 1, 0, path);
    }

    addTo(path: Path) {
        this.to.push.apply(this.to, path.getSiblings());
    }

    find(): Path {
        var result: Path,
            current: Path,
            operationsCount = 0,
            from = this.from,
            to = this.to,
            closedNodes = [];
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

            current.eachAvailableStep((path: Path) => {
                if (closedNodes[path.uid]) {
                    return;
                }
                if (DEBUG_ENABLED) {
                    path.connection.draw();
                    if (isNaN(path.uid)) {
                        debugger;
                    }
                }
                this.internalAddFrom(path);
            });
        }

        this.operationsCount = operationsCount;
        if (DEBUG_ENABLED) {
            console.log("Search took " + operationsCount + " operations, len = " + result.cost);
            result.draw();
        }
        return result;
    }

    filterNonTraversablePathes(pathes) {
        for (var i = pathes.length - 1; i >= 0; i--) {
            var current = pathes[i];
            if (current.connection.a.used && current.connection.b.used) {
                pathes.splice(i, 1);
                i--;
            }
        }
    }

    getHeuristic(newPath: Path) {
        function weakCompare(point1: Point2d, point2: Point2d) {
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

            if (newDirection.id == toDirection.id) {
                // 3 possible cases
                if ((newDirection.x == 0 && (midNodesDirection.y == 0 || midNodesDirection.y == newDirection.y)) ||
                    newDirection.y == 0 && (midNodesDirection.x == 0 || midNodesDirection.x == newDirection.x)) {
                    anglesCount = ((newDirection.x == 0 && midNodesDirection.x == 0) || (newDirection.y == 0 && midNodesDirection.y == 0)) ? 0 : 2;
                } else {
                    anglesCount = 4;
                }
            } else if (newDirection.id == toDirection.rot180().id) {
                // 2 possible cases
                if ((newDirection.x == 0 && midNodesDirection.x == 0) || (newDirection.y == 0 && midNodesDirection.y == 0)) {
                    anglesCount = 4;
                } else {
                    anglesCount = 2;
                }
            } else {
                // 2 possible cases
                if ((newDirection.x == 0 && (midNodesDirection.y == 0 || (newDirection.y == midNodesDirection.y && (toDirection.x == midNodesDirection.x || midNodesDirection.x == 0)))) ||
                    (newDirection.y == 0 && (midNodesDirection.x == 0 || (newDirection.x == midNodesDirection.x && (toDirection.y == midNodesDirection.y || midNodesDirection.y == 0))))) {
                    anglesCount = 1;
                } else {
                    anglesCount = 3;
                }
            }
        }

        // console.log(newDirection, toDirection, midNodesDirection, anglesCount);

        return newPath.cost + distance + distance * 0.00000001 - sub + anglesCount * GraphConstant.optimizationCornerCost;
    }
}
