import {isRTL, wrap} from 'underscore';
import Flotr from 'flotr2';

Flotr.Graph.prototype.calculateSpacing = wrap(
    Flotr.Graph.prototype.calculateSpacing,
    function(originalCalculateSpacing) {
        originalCalculateSpacing.call(this);

        if (isRTL()) {
            const a = this.axes;
            const p = this.plotOffset;

            // plotWidth is calculated correctly: this.plotWidth  = this.canvasWidth - p.left - p.right;
            // just needed to reset the left offset
            p.left = 0;

            a.x.setScale();
            a.x2.setScale();
            a.y.setScale();
            a.y2.setScale();
        }
    }
);

export default Flotr;
