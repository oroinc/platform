/*global define*/
/*jslint nomen: true*/
define(['oroui/js/items-manager/abstract-model'], function (AbstractModel) {
    'use strict';

    /**
     * @class   oroquerydesigner.itemsManager.ColumnsModel
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
            return func && (func.group_type + ':' + func.group_name + ':' + func.name);
        }
    });
});
