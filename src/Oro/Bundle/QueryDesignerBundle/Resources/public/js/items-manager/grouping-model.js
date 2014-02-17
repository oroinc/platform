/*global define*/
/*jslint nomen: true*/
define(['underscore', 'oroui/js/items-manager/abstract-model'], function (_, AbstractModel) {
    'use strict';

    /**
     * @class   oro.queryDesigner.grouping.Model
     * @extends oroui.itemsManager.AbstractModel
     */
    return AbstractModel.extend({
        defaults: {
            name : null
        },

        getNameLabel: function () {
            var name = this.get('name');
            return name ? this.nameTemplate(this.util.splitFieldId(name)) : '';
        }
    });
});
