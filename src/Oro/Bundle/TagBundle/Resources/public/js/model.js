define(['backbone'], function(Backbone) {
    'use strict';

    /**
     * @export  orotag/js/model
     * @class   orotag.Model
     * @extends Backbone.Model
     */
    const TagModel = Backbone.Model.extend({
        defaults: {
            owner: false,
            notSaved: false,
            moreOwners: false,
            url: '',
            name: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function TagModel(attrs, options) {
            TagModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return TagModel;
});
