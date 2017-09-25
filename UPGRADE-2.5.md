UPGRADE FROM 2.4 to 2.5
=======================

DataGridBundle
--------------
- Class `Oro\Bundle\DataGridBundle\Controller\GridController`
    - removed method `getSecurityToken`
    - removed method `getTokenSerializer`

EntityBundle
------------
- Interface `Oro/Bundle/EntityBundle/Provider/EntityNameProviderInterface`:
    - for second argument of methods `public function getName($format, $locale, $entity)` and `public function getNameDQL($format, $locale, $className, $alias)` now can be used `Oro\Bundle\LocaleBundle\Entity\Localization`.
- Class `Oro\Bundle\EntityBundle\Provider\EntityNameResolver`:
    - for third argument of method `public function getName($entity, $format = null, $locale = null)` now can be used `Oro\Bundle\LocaleBundle\Entity\Localization`.
    - for fourth argument of method `public function getNameDQL($className, $alias, $format = null, $locale = null)` now can be used `Oro\Bundle\LocaleBundle\Entity\Localization`.

EntityConfigBundle
------------------
- Added interface `Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface` that should be implemented in case new type of arguments added.
- Implementation should be registered as service with tag `oro_entity_config.attribute_type`.
- Class `Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeConfigExtension`:
    - changes in constructor:
        - added third argument `Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry $attributeTypeRegistry`.

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

MessageQueue component
----------------------
- Interface `Oro\Component\MessageQueue\Job\ExtensionInterface`
    - renamed method `onCreateDelayed` to `onPostCreateDelayed`
    - added method `onPreCreateDelayed`

SearchBundle
------------
- Entity `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal`:
    - changed decimal field `value`:
        - `precision` changed from `10` to `21`.
        - `scale` changed from `2` to `6`.

SecurityBundle
--------------
 - Class `Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider`
     - internal cache parameter `$tree` was removed cause all cache providers are already automatically decorated by the memory cache provider
