/*global define*/
define(['backbone', './model'
    ], function (Backbone, StateModel) {
    'use strict';

    /**
     * @export  orowindows/js/dialog/state/collection
     * @class   orowindows.dialog.state.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: StateModel,

        url: function () {
            return this.model.prototype.urlRoot;
        }
    });
});
