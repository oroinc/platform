define([
    'backbone'
], function(Backbone) {
    'use strict';

    var EntityModel;

    /**
     * @export  oroform/js/multiple-entity/model
     * @class   oroform.MultipleEntity.Model
     * @extends Backbone.Model
     */
    EntityModel = Backbone.Model.extend({
        defaults: {
            id: null,
            link: null,
            label: null,
            isDefault: false,
            extraData: []
        },

        /**
         * @inheritDoc
         */
        constructor: function EntityModel() {
            EntityModel.__super__.constructor.apply(this, arguments);
        }
    });

    return EntityModel;
});
