define(['backbone'], function(Backbone) {
    'use strict';

    var TagModel;
    /**
     * @export  orotag/js/model
     * @class   orotag.Model
     * @extends Backbone.Model
     */
    TagModel = Backbone.Model.extend({
        defaults: {
            owner: false,
            notSaved: false,
            moreOwners: false,
            url: '',
            name: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function TagModel() {
            TagModel.__super__.constructor.apply(this, arguments);
        }
    });

    return TagModel;
});
