define(function(require) {
    'use strict';

    var BaseNavigationItemCollection;
    var mediator = require('oroui/js/mediator');
    var BaseNavigationModel = require('oronavigation/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    BaseNavigationItemCollection = BaseCollection.extend({
        model: BaseNavigationModel,

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
