define(['./point2d'], function(Point2d) {
    'use strict';
    return {
        LEFT_TO_RIGHT: new Point2d(1, 0),
        RIGHT_TO_LEFT: new Point2d(-1, 0),
        TOP_TO_BOTTOM: new Point2d(0, 1),
        BOTTOM_TO_TOP: new Point2d(0, -1)
    };
});
