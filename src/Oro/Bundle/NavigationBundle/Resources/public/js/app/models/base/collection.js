define([
    'oroui/js/mediator',
    'oroui/js/app/models/base/collection'
], function(mediator, BaseCollection) {
    'use strict';

    var Collection;

    Collection = BaseCollection.extend({
        getCurrentModel: function() {
            var model = this.find(function(model) {
                return mediator.execute('compareUrl', model.get('url'));
            });
            return model;
        }
    });

    return Collection;
});
