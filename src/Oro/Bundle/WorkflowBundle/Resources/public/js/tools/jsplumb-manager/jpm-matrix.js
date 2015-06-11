define(function (require) {
    'use strict';
    var _ = require('underscore'),
        Cell = require('./jpm-cell'),
        Matrix = function (options) {

            _.extend(this, {
                xPadding: options.xPadding,
                yPadding: options.xPadding,
                xIncrement: options.xIncrement,
                yIncrement: options.yIncrement,
                workflow: options.workflow
            });
            var that = this,
                steps = this.workflow.get('steps'),
                orders = _.uniq(_.pluck(steps.toJSON(), 'order'));

            this.width = 8;
            this.cells = _.range(orders.length).map(
                function(n) {
                    return _.range(that.width).map(function(){ return []});
                }
            );
            this.transitions = {};
            this.workflow.get('transitions').each(function(transition) {
                that.transitions[transition.get('name')] = transition;
            });
            this.connections = [];
            _.each(steps.models, function(step){
                _.each(step.get('allowed_transitions'), function (transitionName) {
                    var transition = that.transitions[transitionName],
                        target;
                    if(transition) {
                        target = _.find(steps.models, function(item){
                            return item.get('name') == transition.get('step_to');
                        });
                        if(target) {
                            that.connections.push({
                                from: step.get('name'),
                                to: target.get('name')
                            })
                        }
                    }
                });
            });



            this._fill(steps.models);
        }
    _.extend(Matrix.prototype, {
        move: function (cell, x, y) {
            if (typeof y == 'undefined' || y === false) {
                y = cell.y;
            }
            var dx = x - cell.x,
                dy = y - cell.y;
            if(dx == 0 && dy == 0) {
                return false;
            } else {
                this._move(cell,dx,dy);
                return true;
            }
        },
        remove: function (cell, x, y) {
            var place = this.cells[cell.y][cell.x];
            place.splice(place.indexOf(cell), 1);
            cell.step.set('position', [ 0, -1000]);
            cell = null;
        },
        swap: function (c1, c2) {
            var x = c1.x;
            this.move(c1, c2.x);
            this.move(c2, x);
        },
        _move: function (cell, dx, dy) {
            var place = this.cells[cell.y][cell.x],
                x = cell.x + dx,
                y = cell.y + dy;
            if(this.cells[y][x]) {
                this.cells[y][x].push(cell);
                place.splice(place.indexOf(cell), 1);
                cell.x = x;
                cell.y = y;
            }
        },
        align: function () {
            var empty, minX = this.width, minY = this.cells.length;
            this.forEachCell( function (cell, row, col) {
                minX = Math.min(minX, col);
                minY = Math.min(minY, row);
            });
            if(minY > 0) {
                empty = this.cells.splice(0, minY);
                this.cells = this.cells.concat(empty);
            }
            if(minX > 0) {
                for (var row = 0; row < this.cells.length; row++) {
                    empty = this.cells[row].splice(0, minX);
                    this.cells[row] = this.cells[row].concat(empty);
                }
            }
            return this;
        },
        show: function () {
            this.forEachCell( _.bind(function(cell, row, col){
                cell.step.set('position', [
                    this.xIncrement * col + this.xPadding,
                    this.yIncrement * row + this.yPadding
                ]);
            }, this));
            return this;
        },
        findCell: function (step) {
            var name = typeof step === 'string' ? step : step.get('name');
            for (var row = 0; row < this.cells.length; row++) {
                for (var col = 0; col < this.cells[row].length; col++) {
                    for (var i = 0; i < this.cells[row][col].length; i++) {
                        if (this.cells[row][col][i] && this.cells[row][col][i].name === name) {
                            return this.cells[row][col][i];
                        }
                    }
                }
            }
            return null;
        },
        forEachCell: function(callback) {
            for (var row = 0; row < this.cells.length; row++) {
                for (var col = 0; col < this.cells[row].length; col++) {
                    for (var i = 0; i < this.cells[row][col].length; i++) {
                        if (this.cells[row][col][i]) {
                            callback(this.cells[row][col][i], row, col, i);
                        }
                    }
                }
            }
            return this;
        },
        _fill: function(steps) {
            var row, col, key, groupedSteps = _.groupBy(steps, function (item) {
                    return item.get('order');
                }),
                sortedKeys = _.each(_.keys(groupedSteps), parseInt).sort();
            //fill cells
            for (row = 0; row < sortedKeys.length; row++) {
                key = sortedKeys[row];
                for (col = 0; col < groupedSteps[key].length; col++) {
                    this.cells[row][col + 2] = [new Cell({
                        x: col + 2,
                        y: row,
                        step: groupedSteps[key][col]
                    })];
                }
            }
            //set children
            for (row = 0; row < this.cells.length; row++) {
                for (col = 0; col < this.cells[row].length; col++) {
                    if(this.cells[row][col].length) {
                        _.each(this.cells[row][col], _.bind(function(item){
                            item.setChildren(this.findChildren(item));
                        }, this));
                    }
                }
            }
        },

        findChildren: function (parent) {
            var transitions = this.transitions,
                children = [];
            _.each(parent.step.get('allowed_transitions'), _.bind(function(key){
                var cell;
                if(key in transitions) {
                    cell = this.findCell(transitions[key].get('step_to'));
                    if (cell && children.indexOf(cell) < 0) {
                        children.push(cell);
                    }
                }
            }, this));
            //children.sort(function(){ return Math.random() > 0.5});
            return children;
        },

        getCoords: function (cell) {
            return {
                x: this.xIncrement * cell.x + this.xPadding,
                y: this.yIncrement * cell.y + this.yPadding
            };
        }
    });

    return Matrix;
});
