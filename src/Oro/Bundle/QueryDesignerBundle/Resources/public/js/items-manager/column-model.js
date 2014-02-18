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
        }
    });
});
