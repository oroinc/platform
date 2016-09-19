UPGRADE FROM 1.10.2 to 1.10.3
=============================

####EntityExtendBundle
- `Oro\Bundle\EntityExtendBundle\Command\CacheCommand::setClassAliases` no longer throws `\ReflectionException`
- `Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle::checkConfigs` and `Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle::initializeCache`
throws `\RuntimeException` if cache initialization failed. Make sure you don't autoload extended entity classes during container compilation.
- `cache_warmer` is decorated to allow disable cache warming during extend commands calls. Tag your warmer with `oro_entity_extend.warmer`
tag if it works with extend classes

####EmailBundle
- `Oro/Bundle/EmailBundle/Cache/EntityCacheClearer` deprecated, tag on `oro_email.entity.cache.clearer` removed
- `oro_email.email_address.entity_manager` inherits `oro_entity.abstract_entity_manager`
- `Oro/Bundle/EmailBundle/Entity/MailboxProcessSettings` no longer inherits `Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings`
- `Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings` removed

####EntityBundle
- `oro_entity.abstract_repository` introduced. Please inherit all your doctrine repository factory services

Before
```
oro_workflow.repository.workflow_item:
    class: Doctrine\ORM\EntityRepository
    factory:  ["@oro_entity.doctrine_helper", getEntityRepository]
```

After
```
oro_workflow.repository.workflow_item:
    class: 'Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository'
    parent: oro_entity.abstract_repository
```

- `oro_entity.abstract_entity_manager` introduced. Please inherit all your doctrine entity manager factory services

Before
```
oro_email.email_address.entity_manager:
    public: false
    class: Doctrine\ORM\EntityManager
    factory: ['@doctrine', getManagerForClass]
```

After
```
oro_email.email_address.entity_manager:
    parent: oro_entity.abstract_entity_manager
```

####SearchBundle
- `oro_search.entity.repository.search_index` marked as lazy

####WorkflowBundle
- `oro_workflow.repository.workflow_item` inherits `oro_entity.abstract_repository`

####LocaleBundle
- `oro_locale.repository.localization` inherits `oro_entity.abstract_repository`

####DatagridBundle:
- Class `Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource.php`
    - construction signature was changed now it takes next arguments:
        `ConfigProcessorInterface` $processor,
        `EventDispatcherInterface` $eventDispatcher,
        `ParameterBinderInterface` $parameterBinder,
        `QueryHintResolver` $queryHintResolver
- Added class `Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor`
- Added interface `Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\ConfigProcessorInterface`
