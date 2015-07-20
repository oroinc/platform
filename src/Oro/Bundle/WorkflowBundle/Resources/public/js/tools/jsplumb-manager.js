define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var JsPlumbSmartlineManager = require('./jsplumb-smartline-manager');
    var Matrix = require('./jsplumb-manager/jpm-matrix');
    var HideStartRule = require('./jsplumb-manager/jpm-hide-start-rule');
    var CascadeRule = require('./jsplumb-manager/jpm-cascade-rule');
    var PyramidRule = require('./jsplumb-manager/jpm-pyramid-rule');
    var TriadaRule = require('./jsplumb-manager/jpm-triada-rule');
    var CherryRule = require('./jsplumb-manager/jpm-cherry-rule');
    var positions = [0.5, 0.8, 0.2, 0.65, 0.35, 0.6, 0.3, 0.7, 0.4];
    var mids = {
        top: [0.5, 0, 0, -1],
        bottom: [0.5, 1, 0, 1],
        left: [0, 0.5, -1, 0],
        right: [1, 0.5, 1, 0]
    };
    var jsPlumbSmartlineManagers = [];
    var JsPlumbManager = function(jsPlumbInstance, workflow) {
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
        this.xIncrement = 240;
        this.yIncrement = 140;
        this.stepForNew = 10;
        if (JsPlumbManager.getJsPlumbSmartlineManager(jsPlumbInstance) === null) {
            jsPlumbSmartlineManagers.push({
                jsPlumbInstance: jsPlumbInstance,
                jsPlumbSmartlineManagerInstance: new JsPlumbSmartlineManager(jsPlumbInstance)
            });
        }
    };

    JsPlumbManager.getJsPlumbSmartlineManager = function (jsPlumbInstance) {
        var item = _.find(jsPlumbSmartlineManagers, function (item) {
            return item.jsPlumbInstance === jsPlumbInstance;
        });
        if (item) {
            return item.jsPlumbSmartlineManagerInstance;
        } else {
            return null;
        }
    };

    _.extend(JsPlumbManager.prototype, {
        organizeBlocks: function() {
            /*var steps = this.workflow.get('steps').filter(function(item) {
                return !item.get('position');
            });*/
            var matrix = new Matrix({
                workflow: this.workflow
            });
            var ruleTypes = [
                HideStartRule,
                CascadeRule,
                PyramidRule,
                TriadaRule,
                CherryRule
            ];
            var transforms = [];
            matrix.forEachCell(function(cell) {
                _.find(ruleTypes, function(RuleType) {
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
            _.each(transforms, function(rule) {
                rule.apply();

            });
            matrix.align().forEachCell(_.bind(function(cell) {
                cell.step.set('position', [
                    this.xIncrement * cell.x + this.xPadding,
                    this.yIncrement * cell.y + this.yPadding
                ]);
            }, this));
        },

        getPositionForNew: function() {
            var step = this.stepForNew;
            var val = 0;
            var exist = [];
            this.workflow.get('steps').each(function(item) {
                var pos = item.get('position');
                if (pos && pos[0] === pos[1] && pos[0] % step === 0) {
                    exist.push(pos[0]);
                }
            });
            while (_.indexOf(exist, val) >= 0) {
                val += step;
            }
            return [val, val];
        },

        getLoopbackAnchors: function(elId) {
            var presets = this.loopbackAnchorPreset;
            if (!(elId in this.loopback)) {
                this.loopback[elId] = [];
            }
            var preset = presets[this.loopback[elId].length % presets.length];
            this.loopback[elId].push(preset);
            return preset;
        },

        getAnchors: function(sEl, tEl) {
            if (sEl === tEl) {
                return this.getLoopbackAnchors(sEl.id);
            }
            var sp = sEl.getBoundingClientRect();
            var tp = tEl.getBoundingClientRect();
            var sa;
            var ta;
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

        debounceRecalculateConnections: function() {
            return this._debounceRecalculateConnections();
        },

        recalculateConnections: function() {
            function process (ep, anchor) {
                // don't manage Perimeter type anchors
                var i;
                var adjusted;
                var coords = _.clone(anchor);
                var round = $('#' + ep.elementId).hasClass('start-step');
                var edge = getEdge(coords);
                var key = ep.element.id + '_' + edge;
                if (key in that.anchors === false) {
                    that.anchors[key] = [];
                }
                i = that.anchors[key].length % positions.length;
                if (edge === 'top' || edge === 'bottom') {
                    coords[0] = positions[i];
                } else {
                    coords[1] = positions[i];
                }
                if (round) {
                    adjusted = roundAdjust(edge, coords[0], coords[1]);
                    coords[0] = adjusted[0];
                    coords[1] = adjusted[1];
                }
                that.anchors[key].push(coords);
                ep.setAnchor(coords);
            }
            function roundAdjust(edge, x, y) {
                if (x * 2 % 1 === 0 && y * 2 % 1 === 0) {
                    return [x, y];
                }
                var a;
                var b;
                var c = 0.5;
                if (edge === 'top' || edge === 'bottom') {
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
            _.each(that.jsPlumbInstance.getConnections(), function(conn) {
                var anchors;
                var se;
                var te;
                if (_.isArray(conn.endpoints) && conn.endpoints.length === 2) {
                    se = conn.endpoints[0];
                    te = conn.endpoints[1];

                    anchors = that.getAnchors(se.element, te.element);
                    process(se, anchors[0]);
                    process(te, anchors[1]);
                }
            });
        }
    });

    return JsPlumbManager;
});
