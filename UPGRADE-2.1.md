UPGRADE FROM 2.0 to 2.1
========================

ActionBundle
------------
- `Oro\Bundle\ActionBundle\Condition\RouteExists` deprecated because of:
    - work with `RouteCollection` is performance consuming
    - it was used to check bundle presence, which could be done with `service_exists`

DataGridBundle
--------------
 - Class `Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat` was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.

EntityConfigBundle
------------------
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager`

LayoutBundle
------------
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed

SearchBundle
------------
- `DbalStorer` is deprecated. If you need its functionality, please compose your class with `DBALPersistenceDriverTrait`
- Deprecated services and classes:
    - `oro_search.search.engine.storer`
    - `Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer`
- `entityManager` instead of `em` should be used in `BaseDriver` children
- `OrmIndexer` should be decoupled from `DbalStorer` dependency
- Interface `Oro\Bundle\SearchBundle\Engine\EngineV2Interface` marked as deprecated - please, use
`Oro\Bundle\SearchBundle\Engine\EngineInterface` instead
- Return value types in `Oro\Bundle\SearchBundle\Query\SearchQueryInterface` and
`Oro\Bundle\SearchBundle\Query\AbstractSearchQuery` were fixed to support fluent interface

SecurityBundle
--------------
- Service overriding in compiler pass was replaced by service decoration for next services:
    - `sensio_framework_extra.converter.doctrine.orm`
    - `security.acl.dbal.provider`
    - `security.acl.cache.doctrine`
    - `security.acl.voter.basic_permissions`
- Next container parameters were removed:
    - `oro_security.acl.voter.class`
- `Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider`:
    - removed implementation of `Symfony\Component\DependencyInjection\ContainerAwareInterface`
    - removed method `public function setContainer(ContainerInterface $container = null)`
    - removed method `protected function getContainer()`
    - changed the visibility of `$tree` property from `protected` to `private`
    - removed method `public function getCache()`
    - removed method `protected function getTreeData()`
    - removed method `protected function getOwnershipMetadataProvider()`
    - removed method `protected function checkDatabase()`
    - removed method `getManagerForClass($className)`
- `Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider`:
    - removed constant `CACHE_KEY`
    - removed property `protected $em`
    - removed method `public function getCache()`
    - changed the signature of the constructor.
      Old signature: `__construct(EntityManager $em, CacheProvider $cache)`.
      New signature:
        ```
        __construct(
            ManagerRegistry $doctrine,
            DatabaseChecker $databaseChecker,
            CacheProvider $cache,
            MetadataProviderInterface $ownershipMetadataProvider,
            TokenStorageInterface $tokenStorage
        )
        ```

TranslationBundle
-----------------
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader`

##EmailBundle
- Added `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface` and implemented it in `Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer`

##ImapBundle
- Updated `Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor::__construct()` signature to use `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface`.

##ActionBundle
- `Oro\Bundle\ActionBundle\Condition\RouteExists` deprecated because of:
    - work with `RouteCollection` is performance consuming
    - it was used to check bundle presence, which could be done with `service_exists`
    
##SearchBundle
- Interface `Oro\Bundle\SearchBundle\Engine\EngineV2Interface` marked as deprecated - please, use
`Oro\Bundle\SearchBundle\Engine\EngineInterface` instead
- Return value types in `Oro\Bundle\SearchBundle\Query\SearchQueryInterface` and
`Oro\Bundle\SearchBundle\Query\AbstractSearchQuery` were fixed to support fluent interface

WorkflowBundle
--------------
- `Oro\Bundle\WorkflowBundle\Validator\WorkflowValidationLoader`:
    - replaced parameter `ServiceLink $emLink` with `ConfigDatabaseChecker $databaseChecker` in the constructor
    - removed property `protected $emLink`
    - removed property `protected $dbCheck`
    - removed property `protected $requiredTables`
    - removed method `protected function checkDatabase()`
    - removed method `protected function getEntityManager()`
    
