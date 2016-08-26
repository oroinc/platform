UPGRADE FROM 1.10.0 to 1.10.1
=============================

####EntityExtendBundle
- `Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper`
    - `getEntityClassByTableName` deprecated, use `getEntityClassesByTableName` instead
    - removed property `tableToClassMap` in favour of `tableToClassesMap`
- `Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsBuilder
    - construction signature was changed now it takes next arguments:
        `EntityMetadataHelper` $entityMetadataHelper,
        `FieldTypeHelper` $fieldTypeHelper,
        `ConfigManager` $configManager
    - removed property `tableToEntityMap` in favour of `tableToEntitiesMap`
    - renamed method `getEntityClassName` in favour of `getEntityClassNames`
- `Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser`
    - construction signature was changed now it takes next arguments:
        `EntityMetadataHelper` $entityMetadataHelper,
        `FieldTypeHelper` $fieldTypeHelper,
        `ConfigManager` $configManager
