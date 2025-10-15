import __extends from '../../extends';
import AbstractSimpleConstraint from './abstract-simple-constraint';
import settings from '../../settings';
__extends(LeftSimpleConstraint, AbstractSimpleConstraint);
function LeftSimpleConstraint(recomendedStart) {
    AbstractSimpleConstraint.call(this);
    this.recomendedStart = recomendedStart;
}
Object.defineProperty(LeftSimpleConstraint.prototype, 'recommendedEnd', {
    get: function() {
        return this.recomendedStart + this.axis.linesIncluded * settings.recommendedConnectionWidth;
    },
    enumerable: true,
    configurable: true
});
export default LeftSimpleConstraint;
