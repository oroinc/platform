define(['./extends', './interval2d', './point2d', './settings'], function(__extends, Interval2d, Point2d, settings) {
    'use strict';
    __extends(Connection, Interval2d);
    /**
     * Connection between nodes
     *
     * @param {NodePoint} a
     * @param {NodePoint} b
     * @param {Point2d} vector
     * @constructor
     */
    function Connection(a, b, vector) {
        Interval2d.call(this, a, b);
        this.costMultiplier = 1;
        this.traversable = true;
        this.uid = Connection.uidCounter++;
        if (!vector) {
            vector = b.sub(a).unitVector;
        }
        this.vector = vector;
        var vid = vector.id;
        a.connections[vid] = this;
        b.connections[-vid/* vector.rot180().id */] = this;
        if (this.axis.graph.isConnectionUnderRect(this)) {
            this.costMultiplier *= settings.overBlockLineCostMultiplier;
        }
    }

    Connection.uidCounter = 0;

    /**
     * Returns cost
     * @type {Axis}
     */
    Object.defineProperty(Connection.prototype, 'cost', {
        get: function() {
            return this.length * this.axis.costMultiplier * this.costMultiplier +
                (this.a.used || this.b.used ? settings.crossPathCost : 0);
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Returns axis on which this connection is placed on
     * @type {Axis}
     */
    Object.defineProperty(Connection.prototype, 'axis', {
        get: function() {
            return this.a.vAxis === this.b.vAxis ? this.a.vAxis : this.a.hAxis;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Unregisters connection is nodes it links
     */
    Connection.prototype.remove = function() {
        var vid = this.vector.id;
        if (this.a.connections[vid] === this) {
            this.a.connections[vid] = null;
        }
        if (this.b.connections[-vid] === this) {
            this.b.connections[-vid] = null;
        }
    };

    /**
     * Returns sibling node for first one.
     * Please inline where possible
     *
     * @param first {NodePoint}
     * @returns {NodePoint}
     */
    Connection.prototype.second = function(first) {
        return (first === this.a) ? this.b : this.a;
    };

    /**
     * Returns connection direction from <first> node to second
     *
     * @param first {NodePoint}
     * @returns {Point2d}
     */
    Connection.prototype.directionFrom = function(first) {
        return this.b === first ? this.vector.rot180() : this.vector;
    };

    /**
     * Draws connection
     *
     * @param {string} color
     */
    Connection.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'green';
        }
        (new Interval2d(
            new Point2d(this.a.recommendedX, this.a.recommendedY),
            new Point2d(this.b.recommendedX, this.b.recommendedY))
            ).draw(color);
    };

    return Connection;
});
