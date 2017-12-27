define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'oroui/js/mediator',
        'oroentity/js/app/models/entity-relationship-collection'
    ], function(mediator, EntityRelationshipCollection) {
        mediator.setHandler('getEntityRelationshipCollection',
            EntityRelationshipCollection.getEntityRelationshipCollection);
    });
});
