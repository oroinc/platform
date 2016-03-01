/** @lends RouteModel */
define(function(require) {
    'use strict';

    /**
     * Abstraction of elements zoom state
     *
     * @class
     * @augment BaseModel
     * @exports ZoomStateModel
     */
    var ZoomStateModel;
    var BaseModel = require('./base/model');

    // homogeneous matrix
    function applyMatrix(point, matrix) {
        return [
            point[0] * matrix[0] + point[1] * matrix[2] + matrix[4],
            point[0] * matrix[1] + point[1] * matrix[3] + matrix[5]
        ];
    }

    ZoomStateModel = BaseModel.extend(/** @exports RouteModel.prototype */{
        /**
         * @inheritDoc
         * @member {Object}
         */
        defaults: function() {
            return /** lends ZoomStateModel.attributes */ {
                /**
                 * Current zoom level
                 * @type {number}
                 */
                zoom: 1,

                /**
                 * Zoom level change speed
                 * @type {number}
                 */
                zoomSpeed: 1.1,

                /**
                 * X position of zoomable area center
                 * @type {number}
                 */
                dx: 0,

                /**
                 * Y position of zoomable area center
                 * @type {number}
                 */
                dy: 0,

                /**
                 * Auto zoom padding
                 * @type {number}
                 */
                autoZoomPadding: 50
            };
        },

        initialize: function(attributes, options) {
            ZoomStateModel.__super__.initialize.apply(this, arguments);
            if (!options.wrapper || !options.inner) {
                throw new Error('ZoomStateModel requires wrapper and inner options to be passed');
            }
            this.wrapper = options.wrapper;
            this.inner = options.inner;
        },

        getCenter: function() {
            return {
                x: this.wrapper.clientWidth / 2 + this.get('dx'),
                y: this.wrapper.clientHeight / 2 + this.get('dy')
            };
        },

        zoomIn: function(dx, dy) {
            this.setZoom(this.get('zoom') * this.get('zoomSpeed'), dx, dy);
        },

        zoomOut: function(dx, dy) {
            this.setZoom(this.get('zoom') / this.get('zoomSpeed'), dx, dy);
        },

        getPosition: function(el) {
            var pos = {
                left: el.offsetLeft,
                top: el.offsetTop,
                width: el.offsetWidth,
                height: el.offsetHeight
            };

            var style = getComputedStyle(el);
            try {
                if (style.transform && style.transform !== 'none') {
                    var matrix = style.transform
                        .replace('matrix(', '')
                        .replace(')', '')
                        .split(' ')
                        .map(function(str) {return str.trim();})
                        .map(parseFloat);
                    var transformOrigin = style.transformOrigin
                        .split(' ')
                        .map(parseFloat);
                    var transformCenter = [pos.left + transformOrigin[0], pos.top + transformOrigin[1]];
                    var leftTop = applyMatrix([transformCenter[0] - pos.left,
                        transformCenter[1] - pos.top], matrix);
                    var leftBottom = applyMatrix([transformCenter[0] - pos.left,
                        transformCenter[1] - pos.top - pos.height], matrix);
                    var rightTop = applyMatrix([transformCenter[0] - pos.left - pos.width,
                        transformCenter[1] - pos.top], matrix);
                    var rightBottom = applyMatrix([transformCenter[0] - pos.left - pos.width,
                        transformCenter[1] - pos.top - pos.height], matrix);

                    var left = Math.min(leftTop[0], leftBottom[0], rightTop[0], rightBottom[0]);
                    var right = Math.max(leftTop[0], leftBottom[0], rightTop[0], rightBottom[0]);
                    var top = Math.min(leftTop[1], leftBottom[1], rightTop[1], rightBottom[1]);
                    var bottom = Math.max(leftTop[1], leftBottom[1], rightTop[1], rightBottom[1]);
                    pos = {
                        left: left + transformCenter[0],
                        top: top + transformCenter[1],
                        width: right - left,
                        height: bottom - top
                    };
                }
            } catch (e) {
                // ignore
            }
            return pos;
        },

        autoZoom: function() {
            var inner = this.inner;
            var left = Infinity;
            var right = -Infinity;
            var top = Infinity;
            var bottom = -Infinity;
            for (var i = 0; i < inner.children.length; i++) {
                var el = inner.children[i];
                if (el.offsetHeight === 0 || el.offsetWidth === 0) {
                    continue;
                }
                var pos = this.getPosition(el);

                if (left > pos.left) {
                    left = pos.left;
                }
                if (right < pos.left + pos.width) {
                    right = pos.left + pos.width;
                }
                if (top > pos.top) {
                    top = pos.top;
                }
                if (bottom < pos.top + pos.height) {
                    bottom = pos.top + pos.height;
                }
            }

            // calculate zoom level
            var zoomLevel = Math.min(
                1,
                (this.wrapper.clientWidth - this.get('autoZoomPadding') * 2) / (right - left),
                (this.wrapper.clientHeight - this.get('autoZoomPadding') * 2) / (bottom - top)
            );
            var clientCenter = {
                x: this.wrapper.clientWidth / 2,
                y: this.wrapper.clientHeight / 2
            };

            left = zoomLevel * (left - clientCenter.x);
            right = zoomLevel * (right - clientCenter.x);
            top = zoomLevel * (top - clientCenter.y);
            bottom = zoomLevel * (bottom - clientCenter.y);

            var currentCenter = {
                x: (left + right) / 2,
                y: (top + bottom) / 2
            };

            this.set({
                zoom: zoomLevel,
                dx: -currentCenter.x, //zeroPoint.x + currentCenter.x,
                dy: -currentCenter.y + this.get('autoZoomPadding') * (1 - zoomLevel) //zeroPoint.y + currentCenter.y
            });
        },

        setZoom: function(zoom, dx, dy) {
            if (dx === void 0) {
                dx = this.getCenter().x;
            }
            if (dy === void 0) {
                dy = this.getCenter().y;
            }
            var currentZoom = this.get('zoom');
            var zoomSpeed = zoom / currentZoom;
            var center = this.getCenter();
            //console.log(center, dx, dy);
            this.set({
                dx: this.get('dx') - (center.x - dx) * (1 - zoomSpeed),
                dy: this.get('dy') - (center.y - dy) * (1 - zoomSpeed),
                zoom: zoom
            });
        },

        move: function(dx, dy) {
            this.set({
                dx: this.get('dx') + dx,
                dy: this.get('dy') + dy
            });
        }
    });

    return ZoomStateModel;
});
