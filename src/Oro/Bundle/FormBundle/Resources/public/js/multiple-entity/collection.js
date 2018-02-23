define([
    'underscore', 'backbone', './model'
], function(_, Backbone, EntityModel) {
    'use strict';

    var multipleEntityCollection;

    /**
     * @export  oroform/js/multiple-entity/collection
     * @class   oroform.MultipleEntity.Collection
     * @extends Backbone.Collection
     */
    multipleEntityCollection = Backbone.Collection.extend({
        model: EntityModel,

        /**
         * @inheritDoc
         */
        constructor: function multipleEntityCollection() {
            multipleEntityCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.on('change:isDefault', this.onIsDefaultChange, this);
        },

        onIsDefaultChange: function(item) {
            // Only 1 item allowed to be default
            if (item.get('isDefault')) {
                var defaultItems = this.where({isDefault: true});
                _.each(defaultItems, function(defaultItem) {
                    if (defaultItem.get('id') !== item.get('id')) {
                        defaultItem.set('isDefault', false);
                    }
                });
                this.trigger('defaultChange', item);
            }
        }
    });

    return multipleEntityCollection;
});
