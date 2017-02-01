UPGRADE FROM 2.0 to 2.1
========================

##DataGridBundle
 - Class `Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat` was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.

##SearchBundle
- `DbalStorer` is deprecated. If you need its functionality, please compose your class with `DBALPersistenceDriverTrait` 
- Deprecated services and classes:
    - `oro_search.search.engine.storer`
    - `Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer`
- `entityManager` instead of `em` should be used in `BaseDriver` children
- `OrmIndexer` should be decoupled from `DbalStorer` dependency
 
##SecurityBundle
- Service overriding in compiler pass was replaced by service decoration for next services:
    - `sensio_framework_extra.converter.doctrine.orm`
    - `security.acl.dbal.provider`
    - `security.acl.cache.doctrine`
    - `security.acl.voter.basic_permissions`
- Next container parameters were removed:
    - `oro_security.acl.voter.class`

##LayoutBundle
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed

##ActionBundle
- `Oro\Bundle\ActionBundle\Condition\RouteExists` deprecated because of:
    - work with `RouteCollection` is performance consuming
    - it was used to check bundle presence, which could be done with `service_exists`
    
##SearchBundle
- Interface `Oro\Bundle\SearchBundle\Engine\EngineV2Interface` marked as deprecated - please, use
`Oro\Bundle\SearchBundle\Engine\EngineInterface` instead
- Return value types in `Oro\Bundle\SearchBundle\Query\SearchQueryInterface` and
`Oro\Bundle\SearchBundle\Query\AbstractSearchQuery` were fixed to support fluent interface