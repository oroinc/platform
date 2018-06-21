define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var EntityRelationshipCollection = require('oroentity/js/app/models/entity-relationship-collection');

    mediator.setHandler('getEntityRelationshipCollection',
        EntityRelationshipCollection.getEntityRelationshipCollection);
});
