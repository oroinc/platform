import __extends from '../../extends';
import AbstractSimpleConstraint from './abstract-simple-constraint';
__extends(EmptyConstraint, AbstractSimpleConstraint);
function EmptyConstraint(...args) {
    AbstractSimpleConstraint.apply(this, args);
}
Object.defineProperty(EmptyConstraint.prototype, 'recommendedEnd', {
    get: function() {
        return undefined;
    },
    enumerable: true,
    configurable: true
});
export default EmptyConstraint;
