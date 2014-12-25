UPGRADE FROM 1.5 to 1.6
=======================

####OroEntityBundle:

##`Oro\Bundle\EntityBundle\Provider\EntityFieldProvider`
- `setVirtualRelationProvider` was added
- `getFields` method signature changed
    `$withVirtualRelations` added
- `addFields` method signature changed
    `EntityManager $em` removed
    `$withVirtualFields` removed
- `addRelations` method signature changed
    `EntityManager $em` removed
- `addUnidirectionalRelations` method signature changed
    `EntityManager $em` removed
- `addVirtualFields` method signature changed
    `ClassMetadata $metadata` => `$className`
- `isIgnoredField` method signature changed
    `ClassMetadataInfo $metadata` => `ClassMetadata $metadata`
- `getUnidirectionalRelations` method signature changed
    `EntityManager $em` removed
