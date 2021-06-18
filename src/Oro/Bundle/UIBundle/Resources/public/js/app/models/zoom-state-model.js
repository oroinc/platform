define(function(require) {
    'use strict';

    /**
     * Abstraction of elements zoom state
     *
     * @class
     * @augment BaseModel
     * @exports ZoomStateModel
     */
    const BaseModel = require('./base/model');

    // homogeneous matrix
    function applyMatrix(point, matrix) {
        return [
            point[0] * matrix[0] + point[1] * matrix[2] + matrix[4],
            point[0] * matrix[1] + point[1] * matrix[3] + matrix[5]
        ];
    }

    const ZoomStateModel = BaseModel.extend(/** @lends ZoomStateModel.prototype */{
        /**
         * @inheritdoc
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
                 * Minimal allowed zoom level
                 * @type {number}
                 */
                minZoom: 0.01,

                /**
                 * Maximal allowed zoom level
                 * @type {number}
                 */
                maxZoom: 100,

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

        /**
         * @inheritdoc
         */
        constructor: function ZoomStateModel(attrs, options) {
            ZoomStateModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(attributes, options) {
            ZoomStateModel.__super__.initialize.call(this, attributes, options);
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
            let pos = {
                left: el.offsetLeft,
                top: el.offsetTop,
                width: el.offsetWidth,
                height: el.offsetHeight
            };

            const style = getComputedStyle(el);
            try {
                if (style.transform && style.transform !== 'none') {
                    const matrix = style.transform
                        .replace('matrix(', '')
                        .replace(')', '')
                        .split(' ')
                        .map(function(str) {
                            return str.trim();
                        })
                        .map(parseFloat);
                    const transformOrigin = style.transformOrigin
                        .split(' ')
                        .map(parseFloat);
                    const transformCenter = [pos.left + transformOrigin[0], pos.top + transformOrigin[1]];
                    const leftTop = applyMatrix([transformCenter[0] - pos.left,
                        transformCenter[1] - pos.top], matrix);
                    const leftBottom = applyMatrix([transformCenter[0] - pos.left,
                        transformCenter[1] - pos.top - pos.height], matrix);
                    const rightTop = applyMatrix([transformCenter[0] - pos.left - pos.width,
                        transformCenter[1] - pos.top], matrix);
                    const rightBottom = applyMatrix([transformCenter[0] - pos.left - pos.width,
                        transformCenter[1] - pos.top - pos.height], matrix);

                    const left = Math.min(leftTop[0], leftBottom[0], rightTop[0], rightBottom[0]);
                    const right = Math.max(leftTop[0], leftBottom[0], rightTop[0], rightBottom[0]);
                    const top = Math.min(leftTop[1], leftBottom[1], rightTop[1], rightBottom[1]);
                    const bottom = Math.max(leftTop[1], leftBottom[1], rightTop[1], rightBottom[1]);
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
            const inner = this.inner;
            let left = Infinity;
            let right = -Infinity;
            let top = Infinity;
            let bottom = -Infinity;
            for (let i = 0; i < inner.children.length; i++) {
                const el = inner.children[i];
                if (el.offsetHeight === 0 || el.offsetWidth === 0) {
                    continue;
                }
                const pos = this.getPosition(el);

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
            const zoomLevel = Math.min(
                1,
                (this.wrapper.clientWidth - this.get('autoZoomPadding') * 2) / (right - left),
                (this.wrapper.clientHeight - this.get('autoZoomPadding') * 2) / (bottom - top)
            );
            const clientCenter = {
                x: this.wrapper.clientWidth / 2,
                y: this.wrapper.clientHeight / 2
            };

            left = zoomLevel * (left - clientCenter.x);
            right = zoomLevel * (right - clientCenter.x);
            top = zoomLevel * (top - clientCenter.y);
            bottom = zoomLevel * (bottom - clientCenter.y);

            const currentCenter = {
                x: (left + right) / 2,
                y: (top + bottom) / 2
            };

            this.set({
                zoom: zoomLevel,
                dx: -currentCenter.x, // zeroPoint.x + currentCenter.x,
                dy: -currentCenter.y + this.get('autoZoomPadding') * (1 - zoomLevel) // zeroPoint.y + currentCenter.y
            });
        },

        setZoom: function(zoom, dx, dy) {
            if (dx === void 0) {
                dx = this.getCenter().x;
            }
            if (dy === void 0) {
                dy = this.getCenter().y;
            }
            const currentZoom = this.get('zoom');
            const zoomSpeed = zoom / currentZoom;
            const center = this.getCenter();
            zoom = Math.min(zoom, this.get('maxZoom'));
            zoom = Math.max(zoom, this.get('minZoom'));
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
