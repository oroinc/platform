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
    var $ = require('jquery');

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
                dy: 0,

                /**
                 * Auto zoom padding
                 * @type {number}
                 */
                autoZoomPadding: 50,

                /**
                 * Interceptors
                 */
                getPositionInterceptors: {
                    // that is centered using transform
                    '.jsplumb-overlay' : function (el, pos) {
                        pos.left -= pos.width / 2;
                        pos.top -= pos.height / 2;
                    }
                }
            };
        },

        getCenter: function () {
            return {
                x: this.get('wrapper').clientWidth / 2 + this.get('dx'),
                y: this.get('wrapper').clientHeight / 2 + this.get('dy')
            };
        },

        zoomIn: function (dx, dy) {
            if (dx === undefined) {
                dx = this.getCenter().x;
            }
            if (dy === undefined) {
                dy = this.getCenter().y;
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
            if (dx === undefined) {
                dx = this.getCenter().x;
            }
            if (dy === undefined) {
                dy = this.getCenter().y;
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

        getPosition: function (el) {
            var positionInterceptors = this.get('getPositionInterceptors');
            var $el = $(el);
            var pos = {
                left: el.offsetLeft,
                top: el.offsetTop,
                width: el.offsetWidth,
                height: el.offsetHeight
            };

            // another variant use
            // getComputedStyle(el).transform (returns matrix) and
            // getComputedStyle(el).transformOrigin
            // to calculate valid border rect
            for (var query in positionInterceptors) {
                if ($el.is(query)) {
                    positionInterceptors[query](el, pos);
                }
            }
            return pos;
        },

        autoZoom: function () {
            var inner = this.get('inner');
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

            /*
            var $shower = $("<div>&nbsp;</div>").css({
                position: 'absolute',
                top: top,
                left: left,
                width: right - left,
                height: bottom - top,
                border: 'dashed 1px red'
            });
            $(inner).append($shower);
            setTimeout(function (){
                $shower.remove();
            }, 2000);
            */

            // calculate zoom level
            var zoomLevel = Math.min(
                1,
                (this.get('wrapper').clientWidth - this.get('autoZoomPadding') * 2) / (right - left),
                (this.get('wrapper').clientHeight - this.get('autoZoomPadding') * 2) / (bottom - top)
            );
            var clientCenter = {
                x: this.get('wrapper').clientWidth / 2,
                y: this.get('wrapper').clientHeight / 2
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
                dx: - currentCenter.x, //zeroPoint.x + currentCenter.x,
                dy: - currentCenter.y + this.get('autoZoomPadding') * (1 - zoomLevel) //zeroPoint.y + currentCenter.y
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
