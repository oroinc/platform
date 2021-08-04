define([
    'underscore',
    'orotranslation/js/translator',
    'chaplin',
    'oroui/js/app/models/base/collection'
], function(_, __, Chaplin, BaseCollection) {
    'use strict';

    /**
     * @class
     * @augment BaseCollection
     * @exports RoutingCollection
     */
    const BoardDataCollection = BaseCollection.extend(/** @lends RoutingCollection.prototype */{
        /**
         * @inheritdoc
         */
        constructor: function BoardDataCollection(...args) {
            BoardDataCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            BoardDataCollection.__super__.initialize.call(this, models, options);
        },

        updateBoardItem: function(item, update) {
            item = this.get(item.get('id'));
            const itemIndex = this.indexOf(item);
            let insertIndex;
            if (update.insertAfter) {
                update.insertAfter = this.get(update.insertAfter);
                if (!update.insertAfter) {
                    throw new Error('`insertAfter` contains item which does not belong to this collection');
                }
                this.models.splice(itemIndex, 1);
                insertIndex = this.indexOf(update.insertAfter);
                this.models.splice(insertIndex + 1, 0, item);
            } else if (update.insertBefore) {
                update.insertBefore = this.get(update.insertBefore);
                if (!update.insertBefore) {
                    throw new Error('`insertBefore` contains item which does not belong to this collection');
                }
                insertIndex = this.indexOf(update.insertBefore);
                this.models.splice(insertIndex, 0, item);
            } else {
                this.models.splice(itemIndex, 1);
                this.models.unshift(item);
            }
            if (update.properties) {
                item.set(update.properties, {silent: true});
            }
            this.trigger('sort');
        }
    });

    return BoardDataCollection;
});
