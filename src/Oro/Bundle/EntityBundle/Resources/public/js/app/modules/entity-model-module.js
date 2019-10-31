define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const EntityRelationshipCollection = require('oroentity/js/app/models/entity-relationship-collection');

    mediator.setHandler('getEntityRelationshipCollection',
        EntityRelationshipCollection.getEntityRelationshipCollection);
});
