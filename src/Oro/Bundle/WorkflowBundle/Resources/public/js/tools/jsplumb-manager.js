define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Matrix = require('./jsplumb-manager/jpm-matrix');
    var HideStartRule = require('./jsplumb-manager/jpm-hide-start-rule');
    var CascadeRule = require('./jsplumb-manager/jpm-cascade-rule');
    var PyramidRule = require('./jsplumb-manager/jpm-pyramid-rule');
    var TriadaRule = require('./jsplumb-manager/jpm-triada-rule');
    var CherryRule = require('./jsplumb-manager/jpm-cherry-rule');
    var mids = {
        top: [0.5, 0, 0, -1],
        bottom: [0.5, 1, 0, 1],
        left: [0, 0.5, -1, 0],
        right: [1, 0.5, 1, 0]
    };
    var JsPlumbManager = function(jsPlumbInstance, workflow) {
        this.jsPlumbInstance = jsPlumbInstance;
        this.workflow = workflow;
        this.loopback = {};
        this.loopbackAnchorPreset = [
            [[1, 0.3, 1, 0], [0.8, 0, 0, -1]],
            [[0.2, 1, 0, 1], [0, 0.7, -1, 0]],
            [[1, 0.5, 1, 0], [0.5, 0, 0, -1]],
            [[0.5, 1, 0, 1], [0, 0.5, -1, 0]]
        ];
        this.xPadding = 80;
        this.yPadding = 15;
        this.xIncrement = 240;
        this.yIncrement = 140;
        this.stepForNew = 10;
    };

    _.extend(JsPlumbManager.prototype, {
        organizeBlocks: function() {
            /* var steps = this.workflow.get('steps').filter(function(item) {
                return !item.get('position');
            }); */
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
                var increment = cell.step.get('_is_start') ? -15 : 35;
                cell.step.set('position', [
                    this.xIncrement * cell.x + this.xPadding,
                    this.yIncrement * cell.y + this.yPadding + increment
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
        }
    });

    return JsPlumbManager;
});
