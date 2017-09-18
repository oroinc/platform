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
