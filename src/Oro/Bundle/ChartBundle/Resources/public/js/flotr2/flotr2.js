/*global define*/
define([
    'flotr2'
], function (Flotr) {
    'use strict';

    Flotr.defaultOptions.title = ' ';

    var originalInitCanvas = Flotr.Graph.prototype._initCanvas;
    Flotr.Graph.prototype._initCanvas = function () {
        var result = originalInitCanvas.apply(this, arguments);

        if (!this._oro_initialized) {
            this.canvasWidth -= 10;
            this._oro_initialized = true;
        }

        return result;
    };

    return Flotr;
});
