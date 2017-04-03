UPGRADE FROM 2.1 to 2.2
=======================

ActionBundle
------------
- Class `Oro\Bundle\DataGridBundle\Extension\Action\Listener\ButtonsListener`:
    - renamed to `Oro\Bundle\ActionBundle\Datagrid\Provider\DatagridActionButtonProvider`
    - refactored to implement `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface`
    - removed class property `protected $searchContext = []`
    - changed signature of method `protected function getRowConfigurationClosure(DatagridConfiguration $configuration, ButtonSearchContext $context)`
    - added second argument `ButtonSearchContext $context` to method `protected function applyActionsConfig()`
    - added second argument `ButtonSearchContext $context` to method `protected function processMassActionsConfig()`
- Service `oro_action.datagrid.event_listener.button` now has name `oro_action.datagrid.action.button_provider` and registered through the tag `oro_datagrid.extension.action.provider`
- Added `Oro\Bundle\ActionBundle\Model\AbstractGuesser`:
    - defined as abstract service `oro_action.abstract_guesser` with arguments `@form.registry, @doctrine, @oro_entity_config.provider.entity, @oro_entity_config.provider.form`
    - added constructor with arguments `FormRegistry $formRegistry`, `ManagerRegistry $managerRegistry`, `ConfigProvider $entityConfigProvider`, `ConfigProvider $formConfigProvider`
    - extracted methods from `Oro\Bundle\ActionBundle\Model\AttributeGuesser`:
        - `addDoctrineTypeMapping` with arguments: `$doctrineType, $attributeType, array $attributeOptions = []`
        - `addFormTypeMapping with` with arguments: `$variableType, $formType, array $formOptions = []`
        - `guessMetadataAndField` with arguments: `$rootClass, $propertyPath`
        - `guessParameters` with arguments: `$rootClass, $propertyPath`
        - `setDoctrineTypeMappingProvider` with argument: `DoctrineTypeMappingProvider $doctrineTypeMappingProvider = null`
- Class `Oro\Bundle\ActionBundle\Model\AttributeGuesser`:
    - now extends `Oro\Bundle\ActionBundle\Model\AbstractGuesser`
    - service `oro_action.attribute_guesser` has parent defined as `oro_action.abstract_guesser`

ActivityBundle
--------------
- Class `Oro\Bundle\ActivityBundle\Provider\ContextGridProvider` was removed
- Class `Oro\Bundle\ActivityBundle\Controller\ActivityController`
    - action `contextAction` is rendered in `OroDataGridBundle:Grid/dialog:multi.html.twig`
    - action `contextGridAction` was removed
    
ConfigBundle
--------------
- Class `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager`:
    - added third argument `EventDispatcherInterface $eventDispatcher`
    - abstract service `oro_config.scope_manager.abstract` now has third argument defined as `@event_dispatcher`
- Class `ConfigManagerScopeIdUpdateEvent` was added

DataAuditBundle
---------------
A new string field `ownerDescription` with the database column `owner_description` was added to the entity 
`Oro\Bundle\DataAuditBundle\Entity\Audit` and to the base class `Oro\Bundle\DataAuditBundle\Entity\AbstractAudit`

DataGridBundle
--------------
- Interface `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface` added.
- Tag `oro_datagrid.extension.action.provider` added. To be able to register by `DatagridActionProviderInterface` any datagrid action configuration providers.
- Class `Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension` (`@oro_datagrid.extension.action`) fourth `__construct` argument (`Symfony\Component\EventDispatcher\EventDispatcherInterface`) were removed.
- Removed event `oro_datagrid.datagrid.extension.action.configure-actions.before`, now it is a call of `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface::hasActions` of registered through a `oro_datagrid.extension.action.provider` tag services.
- Interface `Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface`
    - the signature of method `getDatagrid` was changed - added new parameter `array $additionalParameters = []`.
    
TestFrameworkBundle
-------------------
- added fourth (boolean) parameter to `\Oro\Bundle\TestFrameworkBundle\Test\WebTestCase::runCommand` `$exceptionOnError` to throw `\RuntimeException` when command should executes as utility one.  

AnnotationsReader
--------------
- Methods in class `Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader` were removed:
    - `setRouter`
    - `setCache`
- Signature of class `Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader` was changed:
    - use `Doctrine\Common\Annotations\Reader` as first argument instead of `Symfony\Component\HttpFoundation\RequestStack`
    - use `Symfony\Component\Routing\Router` as second argument
    - use `Doctrine\Common\Cache\Cache` as third argument
- Methods in class `Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType` were removed:
    - `setReaderRegistry`
    - `setTitleTranslator`
    - `setTitleServiceLink`
- Signature of class `Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType` was changed:
    - use `Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry` as second argument instead of `Doctrine\Common\Persistence\ManagerRegistry`
    - use `Oro\Bundle\NavigationBundle\Provider\TitleTranslator` as third argument instead of `Symfony\Component\Translation\TranslatorInterface`
    - use `Oro\Component\DependencyInjection\ServiceLink` as fourth argument

IntegrationBundle
-----------------
- Class `Oro\Bundle\IntegrationBundle\Async\ReversSyncIntegrationProcessor`
    - construction signature was changed now it takes next arguments:
        - `DoctrineHelper` $doctrineHelper,
        - `ReverseSyncProcessor` $reverseSyncProcessor,
        - `TypesRegistry` $typesRegistry,
        - `JobRunner` $jobRunner,
        - `TokenStorageInterface` $tokenStorage,
        - `LoggerInterface` $logger
        
WorkflowBundle
--------------
- Changed implemented interface of  `Oro\Bundle\WorkflowBundle\Model\Variable` class from `Oro\Bundle\ActionBundle\Model\ParameterInterface` to `Oro\Bundle\ActionBundle\Model\EntityParameterInterface`
- Class `Oro\Bundle\WorkflowBundle\Model\VariableGuesser`:
    - removed constructor
    - now extends `Oro\Bundle\ActionBundle\Model\AbstractGuesser`
    - service `oro_workflow.variable_guesser` has parent defined as `oro_action.abstract_guesser`
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener` added
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener` auto start workflow part were moved into `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener`
- Added parameter `$activeOnly` (boolean) with default `false` to method `\Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository::getAllRelatedEntityClasses`
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache` added:
    - **purpose**: to check whether an entity has been involved as some workflow related entity in cached manner to avoid DB calls
    - **methods**:
        - `hasRelatedActiveWorkflows($entity)`
        - `hasRelatedWorkflows($entity)`
    - invalidation of cache occurs on workflow changes events: 
        - `oro.workflow.after_update`
        - `oro.workflow.after_create`
        - `oro.workflow.after_delete`
        - `oro.workflow.activated`
        - `oro.workflow.deactivated`
- Service `oro_workflow.cache` added with standard `\Doctrine\Common\Cache\Cache` interface under namespace `oro_workflow`
- Class `Oro\Bundle\WorkflowBundle\Autocomplete\WorkflowReplacementSearchHandler` was removed
- Class `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType` renamed to `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType`
    
CurrencyBundle
--------------
- Interface `Oro\Bundle\MultiCurrencyBundle\Query\CurrencyQueryBuilderTransformerInterface`:
    - added method `getTransformSelectQueryForDataGrid` that allow to use query transformer in datagrid config
