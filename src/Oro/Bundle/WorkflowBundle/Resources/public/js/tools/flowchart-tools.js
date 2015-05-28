define(function (require) {
    'use strict';
    var _ = require('underscore'),
        Cell = (function () {
            function recurCalcTree(cell, ignore) {
                var num = 1;
                ignore.push(cell);
                _.each(cell.children, function (child) {
                    if(ignore.indexOf(child) < 0 && child.y >= cell.y) {
                        ignore.push(child);
                        num += recurCalcTree(child, ignore);
                    }
                });
                return num;
            }
            var Cell = function (options) {
                _.extend(this, options);
                _.defaults(this, {
                    children: []
                });
                this.name = this.step.get('name');
            }
            _.extend(Cell.prototype, {
                setChildren: function (cells) {
                    this.children = cells
                },
                hasChild: function(cell){
                    return this.children.indexOf(cell) >= 0;
                },
                isRelativeWith: function(cell){
                    return this.hasChild(cell) || cell.hasChild(this);
                },
                calculateTree: function() {
                    var ignore = [], num;
                    num = recurCalcTree(this, ignore);
                    return num;
                }
            });

            return Cell;
        } ()),
        Matrix = (function () {

            function intersect(ax1,ay1,ax2,ay2,bx1,by1,bx2,by2) {
                var v1, v2, v3, v4;
                v1 = (bx2 - bx1) * (ay1 - by1) - (by2 - by1) * (ax1 - bx1);
                v2 = (bx2 - bx1) * (ay2 - by1) - (by2 - by1) * (ax2 - bx1);
                v3 = (ax2 - ax1) * (by1 - ay1) - (ay2 - ay1) * (bx1 - ax1);
                v4 = (ax2 - ax1) * (by2 - ay1) - (ay2 - ay1) * (bx2 - ax1);
                return (v1 * v2 < 0) && (v3 * v4 < 0);
            }

            var Matrix = function (options) {

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
                            //var transactions = this.findRelatedTransitions(groupedSteps[row][col]);
                            this.cells[row][col + 2] = [new Cell({
                                x: col + 2,
                                y: row,
                                step: groupedSteps[key][col]
                                //transitions: transactions,
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
                },

                calculateIntersect: function () {
                    var that = this, coords = [], i, j, counter = 0;
                    _.each(this.connections, function (item) {
                        coords.push({
                            from: that.getCoords(that.findCell(item.from)),
                            to: that.getCoords(that.findCell(item.to))
                        })
                    });
                    for ( i = 0; i < coords.length - 1; i++) {
                        for (j = i + 1; j < coords.length; j++) {
                            if (intersect(coords[i].from.x, coords[i].from.y, coords[i].to.x, coords[i].to.y,coords[j].from.x, coords[j].from.y, coords[j].to.x, coords[j].to.y)) {
                                counter++;
                            }
                        }
                    }
                    return counter;
                }
            });

            return Matrix;
        } ()),
        Rule = (function () {
            var Rule = function (context) {
                this.context = context || this;
                this.root = null;
                this.items = [];
            }
            _.extend(Rule.prototype, {

                prio: 10,

                match: function(){
                    return false;
                },

                apply: function(){
                    return;
                }
            });

            Rule.extend = function(protoProps, staticProps) {
                var parent = this;
                var child;

                if (protoProps && _.has(protoProps, 'constructor')) {
                    child = protoProps.constructor;
                } else {
                    child = function(){ return parent.apply(this, arguments); };
                }

                _.extend(child, parent, staticProps);

                var Surrogate = function(){ this.constructor = child; };
                Surrogate.prototype = parent.prototype;
                child.prototype = new Surrogate;

                if (protoProps) _.extend(child.prototype, protoProps);

                child.__super__ = parent.prototype;

                return child;
            };

            return Rule;
        } ()),

        HideStartRule = Rule.extend({
            name: 'HideStart',
            match: function (cell) {
                if (cell.step.get('order') < 0 && cell.children.length < 1) {
                    this.root = cell;
                    this.items = [];
                    return true;
                } else {
                    this.root = null;
                    this.items = [];
                    return false;
                }
            },
            apply: function() {
                var context = this.context,
                    changed = false;
                if(this.root !== null) {
                    changed = true;
                    context.remove(this.root);
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),
        CascadeRule = Rule.extend({
            name: 'Cascade',
            match: function (cell) {
                var child1 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 1;
                }), child2 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 2;
                }), child3 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 3;
                });

                if (child1 && child2 && child3) {
                    this.root = cell;
                    this.items = [child1, child2, child3];
                    return true;
                } else {
                    this.root = null;
                    this.items = [];
                    return false;
                }
            },
            apply: function() {
                var context = this.context,
                    changed = false;
                if(this.root !== null) {
                    changed = context.move(this.items[0], this.root.x + 2) || changed;
                    changed = context.move(this.items[1], this.root.x + 1) || changed;
                    changed = context.move(this.items[2], this.root.x) || changed;
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),
        LongCascadeRule = Rule.extend({
            name: 'LongCascade',
            match: function (cell) {
                var child1 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 2;
                }), child2 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 3;
                }), child3 = _.find(cell.children, function(child) {
                    return child.y == cell.y + 4;
                });

                if (child1 && child2 && child3) {
                    this.root = cell;
                    this.items = [child1, child2, child3];
                    return true;
                } else {
                    this.root = null;
                    this.items = [];
                    return false;
                }
            },
            apply: function() {
                var context = this.context,
                    changed = false;
                if(this.root !== null) {
                    changed = context.move(this.items[0], this.root.x + 2) || changed;
                    changed = context.move(this.items[1], this.root.x + 1) || changed;
                    changed = context.move(this.items[2], this.root.x) || changed;
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),
        CherryRule = Rule.extend({
            name: 'Cherry',
            match: function(cell) {
                var children = _.filter(cell.children, function(child) {
                    return child.y > cell.y;
                });
                if(cell.step.get('order') >= 0 && children.length == 2) {
                    this.root = cell;
                    this.items = children;
                    return true;
                } else {
                    this.root = null;
                    this.items = [];
                    return false;
                }
            },
            apply: function() {
                var context = this.context,
                    changed = false;
                if (this.root !== null) {
                    if(this.items[0].x > this.items[1].x) {
                        this.items.reverse();
                    }
                    if(this.items[0].calculateTree() < this.items[1].calculateTree()) {
                        changed = true;
                        context.swap(this.items[0], this.items[1]);
                        this.items.reverse();
                    }
                    if (this.items[1].x - this.items[0].x < 2) {
                        changed = context.move(this.items[1], this.items[0].x + 2) || changed;
                    }
                    context.move(this.root, this.items[0].x + 1);
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),
        TriadaRule = Rule.extend({
            name: 'Triada',
            match: function(cell) {
                var children = _.filter(cell.children, function(child) {
                    return child.y == cell.y + 1 || child.y == cell.y + 2;
                });
                children.sort(function(a, b) {
                    return a.y > b.y;
                });
                if(children.length == 3) {
                    this.root = cell;
                    this.items = children;
                    return true;
                } else {
                    this.root = null;
                    this.items = [];
                    return false;
                }
            },
            apply: function() {
                var context = this.context,
                    average,
                    changed = false;
                if(this.root) {
                    average = Math.floor((this.items[0].x + this.items[1].x + this.items[2].x) / 3);
                    changed = context.move(this.root, average) || changed;
                    changed = context.move(this.items[0], average - 1) || changed;
                    changed = context.move(this.items[1], average) || changed;
                    changed = context.move(this.items[2], average + 1) || changed;
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),
        PyramidRule = Rule.extend({
            name: 'Pyramid',
            prio: 20,
            match: function(cell) {
                var a, b, c, temp, children = _.filter(cell.children, function(child) {
                    return child.y == cell.y + 1 || child.y == cell.y + 2;
                });
                children.sort(function(a, b) {
                    return a.x - b.x;
                });


                if(children.length == 3) {
                    this.items = [];
                    for (a = 0; a < 3; a++) {
                        b = (a + 1) % 3;
                        c = (a + 2) % 3;
                        if(children[a].isRelativeWith(children[b]) && children[a].isRelativeWith(children[c])) {
                            this.items = [children[b], children[a], children[c]];
                        }
                    }
                    if (this.items.length == 0) {
                        for (a = 0; a < 3; a++) {
                            b = (a + 1) % 3;
                            c = (a + 2) % 3;
                            if (children[a].isRelativeWith(children[b])) {
                                if(children[a].y < children[b].y && a < b) {
                                    temp = children[a];
                                    children[a] = children[b];
                                    children[b] = temp;
                                }
                                this.items = children;
                                break;
                            }
                        }
                    }
                    if (this.items.length > 0) {
                        this.root = cell;
                        return true;
                    }
                }
                this.root = null;
                this.items = [];
                return false;
            },
            apply: function() {
                var context = this.context,
                    changed = false;
                if(this.root) {
                    changed = context.move(this.items[0], this.root.x - 1) || changed;
                    changed = context.move(this.items[1], this.root.x) || changed;
                    changed = context.move(this.items[2], this.root.x + 1) || changed;
                    this.root = null;
                    this.items = [];
                }
                return changed;
            }
        }),

    helper = {
        xPadding: 15,
        yPadding: 15,
        xIncrement: 200,
        yIncrement: 125,
        matrix: null,
        transitions: null,
        connections: [],
        bounding: [],
        checkPositions: function (workflow) {
            console.log(this);
            var that = this,
                steps = workflow.get('steps').filter(function (item) {
                    return !item.get('position');
                });
            this.transitions = workflow.get('transitions');
            this.matrix = new Matrix({
                workflow: workflow,
                xPadding: this.xPadding,
                yPadding: this.xPadding,
                xIncrement: this.xIncrement,
                yIncrement: this.yIncrement
            });

            var context = this.matrix,
                cells = [],
                rules = [
                    HideStartRule,
                    CascadeRule,
                    LongCascadeRule,
                    PyramidRule,
                    TriadaRule,
                    CherryRule
                ],
                transforms = [];
            this.matrix.forEachCell(function(item){
                cells.push(item);
            });

            _.each(cells, function (item) {
                _.find(rules, function (type) {
                    var rule = new type(context);
                    if (rule.match(item)) {
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
            console.log(this.matrix.calculateIntersect());
            this.matrix.align().show();
        },

        getTransition: function (step1,step2) {
            var targetName = step2.get('name'),
                allowed = step1.get('allowed_transitions'),
                res = null;
            var transitions = this.transitions.filter(function (item) {
                return allowed.indexOf(item.get('name')) >= 0 && item.get('step_to') === targetName;
            });
            if (transitions.length > 0) {
                res = transitions[0];
            }
            return res;
        },
        isConnected: function (y1,x1,y2,x2) {
            var matrix = this.matrix, step1, step2;
            if(!matrix || Math.min(x1,y1,x2,y2) < 0 || Math.max(y1,y2) > matrix.length - 1
                || Math.max(x1,x2) > matrix[0].length - 1 || !matrix[y1][x1] || !matrix[y2][x2]) {
                return false;
            }
            step1 = matrix[y1][x1].step;
            step2 = matrix[y2][x2].step;
            return !!(this.getTransition(step1, step2) || this.getTransition(step2, step1))
        }
    };


    return helper;
});
