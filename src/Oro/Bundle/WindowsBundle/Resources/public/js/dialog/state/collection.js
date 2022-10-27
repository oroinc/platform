define(['backbone', './model'
], function(Backbone, StateModel) {
    'use strict';

    /**
     * @export  orowindows/js/dialog/state/collection
     * @class   orowindows.dialog.state.Collection
     * @extends Backbone.Collection
     */
    const WindowsCollection = Backbone.Collection.extend({
        model: StateModel,

        url: function() {
            return this.model.prototype.urlRoot;
        },

        /**
         * @inheritdoc
         */
        constructor: function WindowsCollection(...args) {
            WindowsCollection.__super__.constructor.apply(this, args);
        }
    });

    return WindowsCollection;
});
