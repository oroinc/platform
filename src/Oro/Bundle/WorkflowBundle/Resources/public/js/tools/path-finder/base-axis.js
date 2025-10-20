import __extends from './extends';
import Axis from './axis';
__extends(BaseAxis, Axis);
/**
 * Axis that embeds lines placements logic
 *
 * @param {NodePoint} a
 * @param {NodePoint} b
 * @param {Graph} graph
 * @param {number} costMultiplier
 * @param {AbstractConstraint} leftConstraint
 * @param {AbstractConstraint} rightConstraint
 * @param {AbstractLocationDirective} locationDirective
 * @constructor
 */
function BaseAxis(a, b, graph, costMultiplier, leftConstraint, rightConstraint, locationDirective) {
    Axis.call(this, a, b, graph, costMultiplier);
    this.linesIncluded = 1;
    leftConstraint.axis = this;
    rightConstraint.axis = this;
    locationDirective.axis = this;
    this.leftConstraint = leftConstraint;
    this.rightConstraint = rightConstraint;
    this.locationDirective = locationDirective;
}

/**
 * Creates baseAxis from interval
 *
 * @param {Interval2d} interval
 * @param {Graph} graph
 * @param {AbstractConstraint} leftConstraint
 * @param {AbstractConstraint} rightConstraint
 * @param {AbstractLocationDirective} locationDirective
 * @returns {BaseAxis}
 */
BaseAxis.createFromInterval = function(interval, graph, leftConstraint, rightConstraint, locationDirective) {
    const costMultiplier = interval.costMultiplier;
    const isVertical = interval.isVertical;
    const axis = new BaseAxis(interval.a, interval.b, graph, costMultiplier, leftConstraint, rightConstraint,
        locationDirective);
    // this is fix for zero length axises
    if (isVertical !== undefined) {
        axis.isVertical = isVertical;
    }
    return axis;
};
export default BaseAxis;
