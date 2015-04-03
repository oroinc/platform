/*global define*/
define([
    'flotr2'
], function (Flotr) {
    'use strict';

    Flotr.defaultOptions.title = ' ';

    /**
     * Squeezes labels together so they're not readable
     */
    var originalCalculateSpacing = Flotr.Graph.prototype.calculateSpacing;
    Flotr.Graph.prototype.calculateSpacing = function () {
        var result = originalCalculateSpacing.apply(this, arguments);

        this.plotWidth -= (this.axes.x.maxLabel.width / 2);
        this.axes.x.length = this.plotWidth;
        this.axes.x2.length = this.plotWidth;

        this.axes.x.setScale();
        this.axes.x2.setScale();

        return result;
    };

    return Flotr;
});
