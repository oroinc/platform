define(['backbone', './model'
], function(Backbone, StateModel) {
    'use strict';

    var WindowsCollection;
    /**
     * @export  orowindows/js/dialog/state/collection
     * @class   orowindows.dialog.state.Collection
     * @extends Backbone.Collection
     */
    WindowsCollection = Backbone.Collection.extend({
        model: StateModel,

        url: function() {
            return this.model.prototype.urlRoot;
        },

        /**
         * @inheritDoc
         */
        constructor: function WindowsCollection() {
            WindowsCollection.__super__.constructor.apply(this, arguments);
        }
    });

    return WindowsCollection;
});
