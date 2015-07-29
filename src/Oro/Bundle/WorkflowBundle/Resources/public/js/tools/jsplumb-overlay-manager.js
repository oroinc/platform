define(function(require) {
    'use strict';
    var JsPlumbOverlayManager;
    var _ = require('underscore');
    var $ = require('jquery');
    var BLOCK_MOVE_ITERATIONS = 16;

    function Block(el) {
        this.name = $(el).find('.step-label').text();
        this.el = el;
        this.x = el.offsetLeft + el.offsetWidth / 2;
        this.y = el.offsetTop + el.offsetHeight / 2;
        this.w = el.offsetWidth;
        this.h = el.offsetHeight;
    }

    _.extend(Block.prototype, {
        isOverlapped: function(block) {
            if (this === block) {
                throw new Error('Incorrect overlap checking with itself.');
            }
            if (block.y - block.h / 2 > this.y + this.h / 2 || block.y + block.h / 2 < this.y - this.h / 2) {
                return false;
            }
            if (block.x + block.w / 2 < this.x - this.w / 2 || block.x - block.w / 2 > this.x + this.w / 2) {
                return false;
            }
            return true;
        }
    });

    function OverlayBlock(el, overlay, points) {
        this.super.apply(this, arguments);
        this.jsPlumbOverlayInstance = overlay;
        this.location = overlay.getLocation();
        this._originalLocation = this.location;
        this.name = $(el).find('.transition-label').text();
        this.moveX = 0;
        this.moveY = 0;
        this.segments = [];
        this.pathLength = 0;
        this._fillSegments(points);
    }

    OverlayBlock.prototype = Object.create(Block.prototype);
    _.extend(OverlayBlock.prototype, {
        super: Block,

        _fillSegments: function(points) {
            var i;
            var segment;
            var p1;
            var p2;
            var totalLength = 0;
            for (i = 1; i < points.length; i++) {
                segment = {};
                p1 = points[i - 1];
                p2 = points[i];
                if (Math.abs(p1.x - p2.x) < 0.1) { // vertical segment
                    segment.orientation = 'v';
                    segment.x = p1.x;
                    segment.y1 = p1.y;
                    segment.y2 = p2.y;
                    totalLength += Math.abs(p1.y - p2.y);
                    this.segments.push(segment);
                } else if (Math.abs(p1.y - p2.y) < 0.1) { // horizontal segment
                    segment.orientation = 'h';
                    segment.y = p1.y;
                    segment.x1 = p1.x;
                    segment.x2 = p2.x;
                    totalLength += Math.abs(p1.x - p2.x);
                    this.segments.push(segment);
                }
            }
            this.pathLength = totalLength;
        },

        setNearestLocation: function(dx, dy) {
            var location, x = this.x + dx, y = this.y + dy, locations = [],  passed = 0;
            _.each(this.segments, function(segment) {
                var min;
                var max;
                if (segment.orientation === 'v') { // vertical segment
                    min = Math.min(segment.y1, segment.y2);
                    max = Math.max(segment.y1, segment.y2);
                    if (y - min >= 0 && max - y >= 0) {
                        locations.push({
                            distance: Math.abs(segment.x - x),
                            passed: segment.y1 < segment.y2 ? passed + y - min : passed + max - y,
                            x : segment.x,
                            y : y,
                            segment: segment
                        });
                    }
                    passed += max - min;
                } else { // horizontal segment
                    min = Math.min(segment.x1, segment.x2);
                    max = Math.max(segment.x1, segment.x2);
                    if (x - min >= 0 && max - x >= 0) {
                        locations.push({
                            distance: Math.abs(segment.y - y),
                            passed: segment.x1 < segment.x2 ? passed + x - min : passed + max - x,
                            x: x,
                            y: segment.y,
                            segment: segment
                        });
                    }
                    passed += max - min;
                }
            }, this);
            location = _.min(locations, function(location) {
                return location.distance;
            });
            this.moveX = 0;
            this.moveY = 0;
            if (_.isObject(location)) {
                if (Math.abs(dx) > 0 || Math.abs(dy) > 0) {
                    // to prevent situation when vector is perpendicular to path segment we move it a bit
                    if (this.x === location.x && location.segment.orientation === 'h') {
                        if (Math.min(location.segment.x1, location.segment.x1) <= location.x - 1) {
                            location.x -= 1;
                            location.passed -= 1;
                        } else if (Math.max(location.segment.x1, location.segment.x1) >= location.x + 1) {
                            location.x += 1;
                            location.passed += 1;
                        }
                    }
                    if (this.y === location.y && location.segment.orientation === 'v') {
                        if (Math.min(location.segment.y1, location.segment.y1) <= location.y - 1) {
                            location.y -= 1;
                            location.passed -= 1;
                        } else if (Math.max(location.segment.y1, location.segment.y1) >= location.y + 1) {
                            location.y += 1;
                            location.passed += 1;
                        }
                    }
                }
                this.x = location.x;
                this.y = location.y;
                this.location = location.passed / this.pathLength;
                return true;
            } else {
                return false;
            }
        },

        setLocation: function(location) {
            var segment, passed = 0;
            segment = _.find(this.segments, function(segment) {
                var min, max, diff;
                if (segment.orientation === 'v') {
                    min = Math.min(segment.y1, segment.y2);
                    max = Math.max(segment.y1, segment.y2);
                    diff = this.pathLength * location - passed;
                    if (diff >= 0 && diff < passed + max - min) {
                        this.x = segment.x;
                        this.y = segment.y1 < segment.y2 ? min + diff : max - diff;
                        this.location = location;
                        return true;
                    }
                    passed += max - min;
                } else { // horizontal segment
                    min = Math.min(segment.x1, segment.x2);
                    max = Math.max(segment.x1, segment.x2);
                    diff = this.pathLength * location - passed;
                    if (diff >= 0 && diff < passed + max - min) {
                        this.x = segment.x1 < segment.x2 ? min + diff : max - diff;
                        this.y = segment.y;
                        this.location = location;
                        return true;
                    }
                    passed += max - min;
                }
            }, this);

            return typeof segment !== 'undefined';
        },

        resetOriginalLocation: function() {
            return this.setLocation(this._originalLocation);
        },

        isChanged: function() {
            return Math.abs(this._originalLocation - this.location) > 0.01;
        },

        clone: function() {
            var clone = new OverlayBlock(this.el, this.jsPlumbOverlayInstance, []);
            clone.setLocation(this.location);
            _.extend(clone, this);
            return clone;
        }
    });

    JsPlumbOverlayManager = function(smartlineManager) {
        this.smartlineManager = smartlineManager;
    };
    _.extend(JsPlumbOverlayManager.prototype, {
        calculate: function() {
            var i;
            var blocks;
            var steps = [];
            var overlays = [];
            if(!this.smartlineManager.isCacheValid()) {
                this.smartlineManager.refreshCache();
            }
            _.each(this.smartlineManager.cache.connections, function(cacheItem) {
                var points = cacheItem.points;
                _.each(cacheItem.connection.getOverlays(), function(overlay) {
                    var block;
                    if (overlay.type === 'Custom') {
                        block = new OverlayBlock(overlay.canvas, overlay, _.clone(points));
                        block.setLocation(0.5);
                        overlays.push(block);
                    }
                }, this);
            });
            _.each(_.keys(this.smartlineManager.jsPlumbInstance.sourceEndpointDefinitions), function(id) {
                var el = document.getElementById(id);
                if (el) {
                    steps.push(new Block(el));
                }
            });
            blocks = steps.concat(overlays);
            for (i = 0; i < BLOCK_MOVE_ITERATIONS; i++) {
                if (!this._moveBlocks(blocks, overlays)) {
                    break;
                }
            }
            _.each(overlays, function(overlay) {
                var overlapped;
                if (overlay.isChanged() ) {
                    overlapped = _.find(blocks, function(block) {
                        var deny = block !== overlay && overlay.isOverlapped(block);
                        return deny;
                    });
                    if (typeof overlapped === 'undefined') {
                        overlay.jsPlumbOverlayInstance.setLocation(overlay.location);
                    } else {
                        overlay.resetOriginalLocation();
                    }
                }
            });
        },

        _moveBlocks: function(blocks, overlays) {
            var changed = false;
            _.each(blocks, function(block) {
                _.each(overlays, function(overlay) {
                    var deltaX, deltaY, multiplier;
                    if (block !== overlay && block.isOverlapped(overlay)) {
                        deltaX = overlay.x - block.x;
                        deltaY = overlay.y - block.y;
                        multiplier = 5 / Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                        deltaX *= multiplier;
                        deltaY *= multiplier;
                        overlay.moveX += deltaX;
                        overlay.moveY += deltaY;
                    }
                });
            });
            _.each(overlays, function(overlay) {
                if (Math.abs(overlay.moveX) >= 1 || Math.abs(overlay.moveY) >= 1) {
                    changed = true;
                    overlay.setNearestLocation(overlay.moveX, overlay.moveY);
                }
            });
            return changed;
        }
    });

    return JsPlumbOverlayManager;
});


