define(function(require) {
    'use strict';

    var TableComponent = function(context) {
        var ComponentId = 'table';
        var domComps = context.DomComponents;
        var dType = domComps.getType('default');
        var dModel = dType.model;
        var dView = dType.view;

        domComps.addType(ComponentId, {
            model: dModel.extend({
                defaults: _.extend(dModel.prototype.defaults, {
                    type: 'table',
                    tagName: 'table',
                    droppable: ['tbody', 'thead', 'tfoot'],
                    classes: ['table', 'table-striped', 'table-bordered']
                }),
                initialize: function(o, opt) {
                    dModel.prototype.initialize.apply(this, arguments);
                    var components = this.get('components');
                    if (!components.length) {
                        components.add({
                            type: 'thead'
                        });
                        components.add({
                            type: 'tbody'
                        });
                        components.add({
                            type: 'tfoot'
                        });
                    }
                }
            }, {
                isComponent: function(el) {
                    var result = '';

                    if (el.tagName === 'TABLE') {
                        result = {
                            type: 'table'
                        };
                    }

                    return result;
                }
            }),
            view: dView
        });
    };

    return TableComponent;
});
