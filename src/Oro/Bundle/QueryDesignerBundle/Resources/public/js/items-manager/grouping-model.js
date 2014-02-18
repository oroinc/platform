/*global define*/
/*jslint nomen: true*/
define(['oroui/js/items-manager/abstract-model'], function (AbstractModel) {
    'use strict';

    /**
     * @class   oroquerydesigner.itemsManager.GroupingModel
     * @extends oroui.itemsManager.AbstractModel
     */
    return AbstractModel.extend({
        defaults: {
            name : null
        }
    });
});
