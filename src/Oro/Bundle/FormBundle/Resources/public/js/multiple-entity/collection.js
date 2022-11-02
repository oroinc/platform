define([
    'underscore', 'backbone', './model'
], function(_, Backbone, EntityModel) {
    'use strict';

    /**
     * @export  oroform/js/multiple-entity/collection
     * @class   oroform.MultipleEntity.Collection
     * @extends Backbone.Collection
     */
    const multipleEntityCollection = Backbone.Collection.extend({
        model: EntityModel,

        /**
         * @inheritdoc
         */
        constructor: function multipleEntityCollection(...args) {
            multipleEntityCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function() {
            this.on('change:isDefault', this.onIsDefaultChange, this);
        },

        onIsDefaultChange: function(item) {
            // Only 1 item allowed to be default
            if (item.get('isDefault')) {
                const defaultItems = this.where({isDefault: true});
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
