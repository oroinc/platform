define(function(require) {
    'use strict';
    var JsPlumbOverlayManager,
        _ = require('underscore'),
        $ = require('jquery'),
        BLOCK_MOVE_ITERATIONS = 16,
    // TODO: remove debug information
        debugVisualization = false;

    // TODO: remove debug information
    function setBorderColor (block) {
        var color = '';
        if ('range' in block === false) {
            color = 'yellow';
        } else if ('x' in block.range) {
            color = 'blue';
        } else if ('y' in block.range) {
            color = 'red';
        }
        $(block.el).css('border-color', color);
    }

    // TODO: remove debug information
    function drawSegment(segment) {
        var x1, x2, y1, y2;
        if (segment.orientation === 'v') {
            x1 = x2 = segment.x;
            y1 = segment.y1;
            y2 = segment.y2;
        } else {
            y1 = y2 = segment.y;
            x1 = segment.x1;
            x2 = segment.x2;
        }
        $('body').append($('<div/>', {'class': 'myrange'}).css({
                'border': '1px solid skyblue',
                'position': 'absolute',
                'left': Math.min(x1, x2) + 'px',
                'width': Math.abs(x1 - x2) + 'px',
                'top': Math.min(y1, y2) + 'px',
                'height': Math.abs(y1 - y2) + 'px'
            })
        );
    }
    // TODO: remove debug information
    function drawBlock(block) {
        $('body').append($('<div/>', {'class': 'myblock'}).css({
                'border': '1px solid red',
                'position': 'absolute',
                'left': block.x + 'px',
                'width': block.w + 'px',
                'top': block.y + 'px',
                'height': block.h + 'px',
                'margin': -block.h / 2 + 'px ' + -block.w / 2 + 'px',
                'z-index': 9999
            }).attr('title', block.name)
        );
    }

    /* class Block
    TODO: move to separate file
     */
    function Block(el) {
        var rect = el.getBoundingClientRect();
        this.name = $(el).find('.step-label').text();
        this.el = el;
        this.x = (rect.left + rect.right) / 2;
        this.y = (rect.top + rect.bottom) / 2;
        this.w = rect.width;
        this.h = rect.height;
        // TODO: remove debug information
        if (debugVisualization) {
            var style = $(el).attr('style');
            $(el).attr('style', style.replace(/border-color\:\s?(blue|red|yellow)/, ''));
        }
    }

    _.extend(Block.prototype, {
        isOverlapped: function (block) {
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

    /* class OverlayBlock
     TODO: move to separate file
     */
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

        _fillSegments: function (points) {
            var i, segment, p1, p2, totalLength = 0;
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

        setNearestLocation: function (dx, dy) {
            var location, x = this.x + dx, y = this.y + dy, locations = [],  passed = 0;
            _.each(this.segments, function (segment) {
                var min, max;
                // TODO: remove debug information
                if (debugVisualization) {
                    drawSegment(segment);
                }
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
                        })
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
                        })
                    }
                    passed += max - min;
                }
            }, this);
            location = _.min(locations, function (location) {
                return location.distance;
            });
            this.moveX = 0;
            this.moveY = 0;
            if (_.isObject(location)) {
                if (Math.abs(dx) > 0 || Math.abs(dy) > 0) {
                    // to prevent situation when vector is perpendicular to path segment we move it a bit
                    if (this.x == location.x && location.segment.orientation === 'h') {
                        if (Math.min(location.segment.x1, location.segment.x1) <= location.x - 1) {
                            location.x -= 1;
                            location.passed -= 1;
                        } else if (Math.max(location.segment.x1, location.segment.x1) >= location.x + 1) {
                            location.x += 1;
                            location.passed += 1;
                        }
                    }
                    if (this.y == location.y && location.segment.orientation === 'v') {
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

        setLocation: function (location) {
            var segment, passed = 0;
            segment = _.find(this.segments, function (segment) {
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

        resetOriginalLocation: function () {
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

    JsPlumbOverlayManager = function (smartlineManager) {
        this.smartlineManager = smartlineManager;
    }
    _.extend(JsPlumbOverlayManager.prototype, {
        calculate: function () {
            // TODO: remove debug information
            if (debugVisualization) {
                $('.myrange, .myblock').remove();
            }
            var i,
                blocks,
                changed,
                steps = [],
                overlays = [];
            _.each(this.smartlineManager.cache, function (cacheItem) {
                var points = cacheItem.path.toPointsArray([]).reverse();
                _.each(cacheItem.connection.getOverlays(), function (overlay) {
                    var block;
                    if (overlay.type === 'Custom') {
                        block = new OverlayBlock(overlay.canvas, overlay, _.clone(points));
                        block.setLocation(0.5);
                        overlays.push(block);
                    }
                }, this);
            });
            _.each(_.keys(this.smartlineManager.jsPlumbInstance.sourceEndpointDefinitions), function (id) {
                var el = document.getElementById(id);
                if (el) {
                    steps.push(new Block(el));
                }
            });
            blocks = steps.concat(overlays);
            for (i = 0; i < BLOCK_MOVE_ITERATIONS; i++) {
                _.each(blocks, function (block) {
                    _.each(overlays, function (overlay) {
                        var deltaX, deltaY, multiplier;
                        if (block !== overlay && block.isOverlapped(overlay)) {
                            deltaX = overlay.x - block.x;
                            deltaY = overlay.y - block.y;
                            multiplier = 5 / Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                            deltaX *= multiplier;
                            deltaY *= multiplier;
                            overlay.moveX += deltaX;
                            overlay.moveY += deltaY;

                            // TODO: remove debug information
                            if (debugVisualization) {
                                setBorderColor(overlay);
                            }
                        }
                    })
                });
                changed = false;
                _.each(overlays, function (overlay) {
                    if (Math.abs(overlay.moveX) >= 1 || Math.abs(overlay.moveY) >= 1) {
                        changed = true;
                        overlay.setNearestLocation(overlay.moveX, overlay.moveY);
                    }
                });
                if (!changed) {
                    break;
                }
            }

            _.each(overlays, function (overlay) {
                var overlapped;
                if (overlay.isChanged() ) {
                    overlapped = _.find(blocks, function (block) {
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
        }
    });

    return JsPlumbOverlayManager;
});


