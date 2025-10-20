import __extends from '../../extends';
import AbstractSimpleConstraint from './abstract-simple-constraint';
import settings from '../../settings';
__extends(RightSimpleConstraint, AbstractSimpleConstraint);
function RightSimpleConstraint(recommendedStart) {
    AbstractSimpleConstraint.call(this);
    this.recomendedStart = recommendedStart;
}
Object.defineProperty(RightSimpleConstraint.prototype, 'recommendedBound', {
    get: function() {
        return this.recomendedStart + this.axis.linesIncluded * settings.recommendedConnectionWidth;
    },
    enumerable: true,
    configurable: true
});
export default RightSimpleConstraint;
