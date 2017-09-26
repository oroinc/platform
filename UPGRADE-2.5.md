UPGRADE FROM 2.4 to 2.5
=======================

MessageQueue component
----------------------
- Interface `Oro\Component\MessageQueue\Job\ExtensionInterface`
    - renamed method `onCreateDelayed` to `onPostCreateDelayed`
    - added method `onPreCreateDelayed`

DataGridBundle
--------------
- Class `Oro\Bundle\DataGridBundle\Controller\GridController`
    - removed method `getSecurityToken`
    - removed method `getTokenSerializer`

ImportExportBundle
------------------
- Class `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract`
    - changed the constructor signature: parameters `TokenStorageInterface $tokenStorage` and `TokenSerializerInterface $tokenSerializer` were removed
    - removed property `tokenStorage`
    - removed property `tokenSerializer`
    - removed method `setSecurityToken`
- Class `Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract`
    - changed the constructor signature: parameter `TokenSerializerInterface $tokenSerializer` was removed
    - removed property `tokenSerializer`
    - removed method `setSecurityToken`
    - renamed method `addDependedJob` to `addDependentJob`
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor` was removed
- Class `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`
    - changed the constructor signature: parameters `TokenStorageInterface $tokenStorage` and `TokenSerializerInterface $tokenSerializer` were removed
- Class `Oro\Bundle\ImportExportBundle\Controller\ImportExportController`
    - removed method `getTokenSerializer`

SecurityBundle
--------------
 - Class `Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider`
     - internal cache parameter `$tree` was removed cause all cache providers are already automatically decorated by the memory cache provider

WorkflowBundle
--------------
- Removed `renderResetButton()` macro from Oro/Bundle/WorkflowBundle/Resources/views/macros.html.twig.
This functionality was deprecated since 2.0. Also removed usage of this macro from two files:
    - `Oro/Bundle/WorkflowBundle/Resources/views/Widget/widget/button.html.twig`
    - `Oro/Bundle/WorkflowBundle/Resources/views/Widget/widget/buttons.html.twig`
