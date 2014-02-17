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
        }
    });
});
