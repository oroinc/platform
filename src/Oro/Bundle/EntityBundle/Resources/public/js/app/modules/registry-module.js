define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'underscore',
        'oroui/js/app/services/registry',
        'oroentity/js/app/models/entity-model',
        'oroentity/js/app/models/entity-relationship-collection'
    ], function(_, registry, EntityModel, EntityRelationshipCollection) {
        registry.declareAccessMethods({
            getEntity: _.partial(EntityModel.getEntity, registry),
            getEntityRelationshipCollection:
                _.partial(EntityRelationshipCollection.getEntityRelationshipCollection, registry)
        });
    });
});
