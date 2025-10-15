import __extends from '../extends';
import AbstractLocationDirective from './abstract-location-directive';
import settings from '../settings';
__extends(CenterLocationDirective, AbstractLocationDirective);
function CenterLocationDirective(...args) {
    AbstractLocationDirective.apply(this, args);
}
CenterLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
    const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
    const requiredWidth = this.axis.linesIncluded * settings.recommendedConnectionWidth;
    return center - requiredWidth / 2 + settings.recommendedConnectionWidth * lineNo;
};
export default CenterLocationDirective;
