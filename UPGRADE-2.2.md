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

ActivityBundle
--------------
- Class `Oro\Bundle\ActivityBundle\Provider\ContextGridProvider` was removed
- Class `Oro\Bundle\ActivityBundle\Controller\ActivityController`
    - action `contextAction` is rendered in `OroDataGridBundle:Grid/dialog:multi.html.twig`
    - action `contextGridAction` was removed

DataGridBundle
--------------
- Interface `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface` added.
- Tag `oro_datagrid.extension.action.provider` added. To be able to register by `DatagridActionProviderInterface` any datagrid action configuration providers.
- Class `Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension` (`@oro_datagrid.extension.action`) fourth `__construct` argument (`Symfony\Component\EventDispatcher\EventDispatcherInterface`) were removed.
- Removed event `oro_datagrid.datagrid.extension.action.configure-actions.before`, now it is a call of `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface::hasActions` of registered through a `oro_datagrid.extension.action.provider` tag services.
- Interface `Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface`
    - the signature of method `getDatagrid` was changed - added new parameter `array $additionalParameters = []`.
