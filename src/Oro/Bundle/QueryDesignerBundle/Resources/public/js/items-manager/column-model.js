/*global define*/
/*jslint nomen: true*/
define(['underscore', 'oroui/js/items-manager/abstract-model'], function (_, AbstractModel) {
    'use strict';

    /**
     * @class   oro.queryDesigner.column.Model
     * @extends oroui.itemsManager.AbstractModel
     */
    return AbstractModel.extend({
        defaults: {
            name : null,
            label: null,
            func: null,
            sorting: null
        },

        getNameLabel: function () {
            var name = this.get('name');
            return name ? this.nameTemplate(this.util.splitFieldId(name)) : '';
        },

        getFuncLabel: function () {
            var func = this.get('func');
            // TODO: functionManager.getFunctionLabel(func.group_type, func.group_name, func.name);
            return func ? func.group_type + ':' + func.name + ' (' + func.group_name + ')' : '';
        }
    });
});
