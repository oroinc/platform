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
                 * Current zoom level
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
                dy: 0
            };
        },

        getCenter: function () {
            return {
                x: this.get('el').clientWidth / 2 + this.get('dx'),
                y: this.get('el').clientHeight / 2 + this.get('dy')
            }
        },

        zoomIn: function (dx, dy) {
            if (!dx) {
                dx = 0;
            }
            if (!dy) {
                dy = 0;
            }
            var zoom = this.get('zoom');
            var center = this.getCenter();
            //console.log(center, dx, dy);
            this.set({
                dx: this.get('dx') - (center.x - dx) * (1 - this.get('zoomSpeed')),
                dy: this.get('dy') - (center.y - dy) * (1 - this.get('zoomSpeed')),
                zoom: zoom * this.get('zoomSpeed')
            });
        },

        zoomOut: function (dx, dy) {
            if (!dx) {
                dx = 0;
            }
            if (!dy) {
                dy = 0;
            }
            var zoom = this.get('zoom');
            var center = this.getCenter();
            //console.log(center, dx, dy);
            this.set({
                dx: this.get('dx') - (center.x - dx) * (1 - 1/this.get('zoomSpeed')),
                dy: this.get('dy') - (center.y - dy) * (1 - 1/this.get('zoomSpeed')),
                zoom: zoom / this.get('zoomSpeed')
            });
        },

        move: function (dx, dy) {
            this.set({
                dx: this.get('dx') + dx,
                dy: this.get('dy') + dy
            });
        }
    });

    return ZoomStateModel;
});
