define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Matrix = require('./jsplumb-manager/jpm-matrix');
    const HideStartRule = require('./jsplumb-manager/jpm-hide-start-rule');
    const CascadeRule = require('./jsplumb-manager/jpm-cascade-rule');
    const PyramidRule = require('./jsplumb-manager/jpm-pyramid-rule');
    const TriadaRule = require('./jsplumb-manager/jpm-triada-rule');
    const CherryRule = require('./jsplumb-manager/jpm-cherry-rule');
    const mids = {
        top: [0.5, 0, 0, -1],
        bottom: [0.5, 1, 0, 1],
        left: [0, 0.5, -1, 0],
        right: [1, 0.5, 1, 0]
    };
    const JsPlumbManager = function(jsPlumbInstance, workflow) {
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
            /* const steps = this.workflow.get('steps').filter(function(item) {
                return !item.get('position');
            }); */
            const matrix = new Matrix({
                workflow: this.workflow
            });
            const ruleTypes = [
                HideStartRule,
                CascadeRule,
                PyramidRule,
                TriadaRule,
                CherryRule
            ];
            const transforms = [];
            matrix.forEachCell(function(cell) {
                _.find(ruleTypes, function(RuleType) {
                    const rule = new RuleType(matrix);
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
            matrix.align().forEachCell(cell => {
                const increment = cell.step.get('_is_start') ? -15 : 35;
                cell.step.set('position', [
                    this.xIncrement * cell.x + this.xPadding,
                    this.yIncrement * cell.y + this.yPadding + increment
                ]);
            });
        },

        getPositionForNew: function() {
            const step = this.stepForNew;
            let val = 0;
            const exist = [];
            this.workflow.get('steps').each(function(item) {
                const pos = item.get('position');
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
            const presets = this.loopbackAnchorPreset;
            if (!(elId in this.loopback)) {
                this.loopback[elId] = [];
            }
            const preset = presets[this.loopback[elId].length % presets.length];
            this.loopback[elId].push(preset);
            return preset;
        },

        getAnchors: function(sEl, tEl) {
            if (sEl === tEl) {
                return this.getLoopbackAnchors(sEl.id);
            }
            const sp = sEl.getBoundingClientRect();
            const tp = tEl.getBoundingClientRect();
            let sa;
            let ta;
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
