import __extends from '../extends';
import AbstractLocationDirective from './abstract-location-directive';
import settings from '../settings';
__extends(StickLeftLocationDirective, AbstractLocationDirective);
function StickLeftLocationDirective(...args) {
    AbstractLocationDirective.apply(this, args);
}
StickLeftLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
    const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
    return center + settings.recommendedConnectionWidth * (lineNo + 0.5);
};
export default StickLeftLocationDirective;
