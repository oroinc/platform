define([
    'backbone'
], function(Backbone) {
    'use strict';

    /**
     * @export  oroform/js/multiple-entity/model
     * @class   oroform.MultipleEntity.Model
     * @extends Backbone.Model
     */
    const EntityModel = Backbone.Model.extend({
        defaults: {
            id: null,
            link: null,
            label: null,
            isDefault: false,
            extraData: []
        },

        /**
         * @inheritdoc
         */
        constructor: function EntityModel(attrs, options) {
            EntityModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return EntityModel;
});
