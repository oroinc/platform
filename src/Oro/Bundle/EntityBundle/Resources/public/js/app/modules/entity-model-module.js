import mediator from 'oroui/js/mediator';
import EntityRelationshipCollection from 'oroentity/js/app/models/entity-relationship-collection';

mediator.setHandler('getEntityRelationshipCollection',
    EntityRelationshipCollection.getEntityRelationshipCollection);
