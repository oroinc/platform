define(function (require) {
    "use strict";

    function getEdge (params) {
        var x = params[0] - 0.5,
            y = params[1] - 0.5,
            edge = '';
        if(Math.abs(y) > Math.abs(x)) {
            edge = y > 0 ? 'bottom' : 'top';
        } else {
            edge = x > 0 ? 'right' : 'left'
        }
        return edge;
    }

    function between(a, b, c) {
        return Math.min(a, c) < b && Math.max(a, c) > b;
    }

    function overlap(a,b) {
        if(a[0] == a[2] && b[0] == b[2] && a[0] == b[0]) {
            return between(a[1], b[1], a[3]) || between(a[1], b[3], a[3]);
        } else if(a[1] == a[3] && b[1] == b[3] && a[1] == b[3]) {
            return between(a[0], b[0], a[2]) || between(a[0], b[2], a[2]);
        }
        return false;
    }

    function intersect(a,b) {
        var v1, v2, v3, v4;
        v1 = (b[2] - b[0]) * (a[1] - b[1]) - (b[3] - b[1]) * (a[0] - b[0]);
        v2 = (b[2] - b[0]) * (a[3] - b[1]) - (b[3] - b[1]) * (a[2] - b[0]);
        v3 = (a[2] - a[0]) * (b[1] - a[1]) - (a[3] - a[1]) * (b[0] - a[0]);
        v4 = (a[2] - a[0]) * (b[3] - a[1]) - (a[3] - a[1]) * (b[2] - a[0]);
        return (v1 * v2 < 0) && (v3 * v4 < 0);
    }

    var _ = require('underscore'),
        $ = require('jquery'),
        Matrix = require('./jsplumb-manager/jpm-matrix'),
        HideStartRule = require('./jsplumb-manager/jpm-hide-start-rule'),
        CascadeRule = require('./jsplumb-manager/jpm-cascade-rule'),
        PyramidRule = require('./jsplumb-manager/jpm-pyramid-rule'),
        TriadaRule = require('./jsplumb-manager/jpm-triada-rule'),
        CherryRule = require('./jsplumb-manager/jpm-cherry-rule'),
        positions = [0.5, 0.8, 0.2, 0.65, 0.35, 0.6, 0.3, 0.7, 0.4],
        mids = {
            'top': [0.5, 0, 0, -1],
            'bottom': [0.5, 1, 0, 1],
            'left': [0, 0.5, -1, 0],
            'right': [1, 0.5, 1, 0]
        },
        JsPlumbManager = function (jsPlumbInstance, workflow) {
            this.jsPlumbInstance = jsPlumbInstance;
            this.workflow = workflow;
            this.loopback = {};
            this.loopbackAnchorPreset = [
                [[1, 0.3, 1, 0],[0.8, 0, 0, -1]],
                [[0.2, 1, 0, 1],[0, 0.7, -1, 0]],
                [[1, 0.5, 1, 0],[0.5, 0, 0, -1]],
                [[0.5, 1, 0, 1],[0, 0.5, -1, 0]]
            ];
            this.xPadding = 80;
            this.yPadding = 15;
            this.xIncrement = 200;
            this.yIncrement = 100;
            this.stepForNew = 10;
            this._debounceRecalculateConnections = _.debounce(_.bind(this.recalculateConnections, this), 100);
        };

    _.extend(JsPlumbManager.prototype, {

        organizeBlocks: function () {
            var steps = this.workflow.get('steps').filter(function (item) {
                    return !item.get('position');
                }),
                matrix = new Matrix({
                    workflow: this.workflow
                });

            var ruleTypes = [
                    HideStartRule,
                    CascadeRule,
                    PyramidRule,
                    TriadaRule,
                    CherryRule
                ],
                transforms = [];
            matrix.forEachCell(function (cell) {
                _.find(ruleTypes, function (RuleType) {
                    var rule = new RuleType(matrix);
                    if (rule.match(cell)) {
                        transforms.push(rule);
                        return true;
                    }
                });
            });
            transforms.sort(function(a, b) {
                return a.root.y > b.root.y;
            });
            _.each(transforms, function (rule) {
                console.log('Rule: ' + rule.name + '; Step: ' + rule.root.step.get('label'));
                rule.apply();

            });
            matrix.align().forEachCell( _.bind(function(cell){
                cell.step.set('position', [
                    this.xIncrement * cell.x + this.xPadding,
                    this.yIncrement * cell.y + this.yPadding
                ]);
            }, this));
        },

        getPositionForNew: function () {
            var step = this.stepForNew,
                val = step,
                exist = [];
            this.workflow.get('steps').each(function (item) {
                var pos = item.get('position');
                if(pos && pos[0] == pos[1] && pos[0] % step == 0) {
                    exist.push(pos[0]);
                }
            })
            while(_.indexOf(exist, val) >= 0) {
                val += step;
            }
            return [val, val];
        },

        getLoopbackAnchors: function (elId) {
            var preset, presets = this.loopbackAnchorPreset;
            if (!(elId in this.loopback)) {
                this.loopback[elId] = [];
            }
            preset = presets[this.loopback[elId].length % presets.length];
            this.loopback[elId].push(preset);
            return preset;
        },

        getAnchors: function (sEl, tEl) {
            if (sEl === tEl) {
                return this.getLoopbackAnchors(sEl.id);
            }
            var sp = sEl.getBoundingClientRect(),
                tp = tEl.getBoundingClientRect(),
                sa, ta;
            if (sp.right < (tp.left + tp.right) / 2) {
                sa = mids.right;
                if (sp.bottom > tp.top) {
                    ta = mids.left;
                } else {
                    ta = mids.top;
                }
            } else {
                sa = mids.bottom;
                ta = mids.top;
            }

            return [sa, ta];
        },

        getIntersections: function(conn) {
            var that = this,
                segs = conn.connector.getAbsSegments(conn),
                collection = [];
            _.each(that.jsPlumbInstance.getConnections(), function (c) {
                if(c !== conn && _.isArray(c.endpoints) && c.endpoints.length === 2) {
                    _.each(c.connector.getAbsSegments(c), function (s1) {
                        _.each(segs, function (s2) {
                            if (intersect(s1, s2) || overlap(s1, s2)) {
                                collection.push(c.overlayView.model.get('label'));
                            }
                        });
                    });
                }
            });
            return collection;
        },

        debounceRecalculateConnections: function () {
            return this._debounceRecalculateConnections();
        },

        recalculateConnections: function () {
            function process (ep, anchor) {
                // don't manage Perimeter type anchors
                var i, adjusted,
                    round = $('#' + ep.elementId).hasClass('start-step'),
                    edge = getEdge(anchor),
                    key = ep.element.id + '_' + edge;
                if(key in that.anchors === false) {
                    that.anchors[key] = [];
                }
                i = that.anchors[key].length % positions.length;
                if( edge === 'top' || edge === 'bottom') {
                    anchor[0] = positions[i];
                } else {
                    anchor[1] = positions[i];
                }
                if (round) {
                    adjusted = roundAdjust(edge, anchor[0], anchor[1]);
                    anchor[0] = adjusted[0];
                    anchor[1] = adjusted[1]
                }
                that.anchors[key].push(anchor);
                ep.setAnchor(anchor);
            }
            function roundAdjust(edge, x, y) {
                if(x * 2 % 1 === 0 && y * 2 % 1 === 0) {
                    return [x, y];
                }
                var a, b, c = 0.5;
                if(edge === 'top' || edge === 'bottom') {
                    b = x - 0.5;
                    a = Math.floor(Math.sqrt(c * c - b * b) * 1000) / 1000;
                    y = 0.5 + a * (edge === 'bottom' ? 1 : -1);
                } else {
                    b = y - 0.5;
                    a = Math.floor(Math.sqrt(c * c - b * b) * 1000) / 1000;
                    x = 0.5 + a * (edge === 'right' ? 1 : -1);
                }
                return [x, y];
            }
            var that = this;
            that.loopback = {};
            that.anchors = {};
             _.each(that.jsPlumbInstance.getConnections(), function (conn) {
                var anchors, se, te;
                if(_.isArray(conn.endpoints) && conn.endpoints.length === 2) {
                    se = conn.endpoints[0];
                    te = conn.endpoints[1];

                    anchors = that.getAnchors(se.element, te.element);
                    process(se, anchors[0]);
                    process(te, anchors[1]);
                }
            });
            console.groupCollapsed('Intersections');
            _.each(that.jsPlumbInstance.getConnections(), function (conn) {
                var is = that.getIntersections(conn),
                    msg = 'Connection "' + conn.overlayView.model.get('label') + '" has ' + (is.length ? is.length : 'not.');
                if(is.length) {
                    msg += ' : ' + is.join(', ');
                }
                console.log(msg);
            });
            console.groupEnd();
        }
    });

    return JsPlumbManager;
});
