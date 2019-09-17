define(function(require) {
    'use strict';

    /**
     * Create responsive table component type for builder
     * @param context
     * @constructor
     */
    var TableResponsiveComponent = function(context) {
        var ComponentId = 'table-responsive';
        var domComps = context.DomComponents;
        var dType = domComps.getType('default');
        var dModel = dType.model;
        var dView = dType.view;

        domComps.addType(ComponentId, {
            model: dModel.extend({
                defaults: _.extend({}, dModel.prototype.defaults, {
                    type: ComponentId,
                    tagName: 'div',
                    draggable: ['div'],
                    droppable: ['table','tbody', 'thead', 'tfoot'],
                    classes: [ComponentId]
                }),
                initialize: function(o, opt) {
                    dModel.prototype.initialize.apply(this, arguments);
                    var components = this.get('components');
                    if (!components.length) {
                        components.add({
                            type: 'table'
                        });
                    }
                }
            }, {
                isComponent: function(el) {
                    var result = '';
                    if (el.tagName === 'DIV' && el.className.indexOf(ComponentId) !== -1) {
                        result = {
                            type: ComponentId
                        };
                    }

                    return result;
                }
            }),
            view: dView
        });
    };

    return TableResponsiveComponent;
});
