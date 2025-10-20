import __extends from '../extends';
import AbstractLocationDirective from './abstract-location-directive';
import settings from '../settings';
__extends(StickRightLocationDirective, AbstractLocationDirective);
function StickRightLocationDirective(...args) {
    AbstractLocationDirective.apply(this, args);
}
StickRightLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
    const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
    const requiredWidth = (this.axis.linesIncluded + 0.5) * settings.recommendedConnectionWidth;
    return center - requiredWidth + settings.recommendedConnectionWidth * lineNo;
};
export default StickRightLocationDirective;
