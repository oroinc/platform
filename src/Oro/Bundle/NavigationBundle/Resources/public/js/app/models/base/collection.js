define([
    'oroui/js/mediator',
    'oroui/js/app/models/base/collection'
], function(mediator, BaseCollection) {
    'use strict';

    var BaseNavigationItemCollection;

    BaseNavigationItemCollection = BaseCollection.extend({
        /**
         * @inheritDoc
         */
        constructor: function BaseNavigationItemCollection() {
            BaseNavigationItemCollection.__super__.constructor.apply(this, arguments);
        },

        getCurrentModel: function() {
            var model = this.find(function(model) {
                return mediator.execute('compareUrl', model.get('url'));
            });
            return model;
        }
    });

    return BaseNavigationItemCollection;
});
