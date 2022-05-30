<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for entity related security metadata.
 */
class EntitySecurityMetadataProvider implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    public const ACL_SECURITY_TYPE = 'ACL';

    private const ALL_PERMISSIONS       = 'All';
    private const PERMISSIONS_DELIMITER = ';';

    private const FULL_CACHE_KEY_PREFIX  = 'full-';
    private const SHORT_CACHE_KEY_PREFIX = 'short-';

    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private CacheItemPoolInterface $cache;
    private EventDispatcherInterface $eventDispatcher;
    private AclGroupProviderInterface $aclGroupProvider;
    /** [security type => [class name => EntitySecurityMetadata, ...], ...] */
    private array $localCache = [];
    /** [security type => [class name => [group, [field name => field alias, ...]], ...], ...] */
    private array $shortLocalCache = [];

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        CacheItemPoolInterface $cache,
        EventDispatcherInterface $eventDispatcher,
        AclGroupProviderInterface $aclGroupProvider
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
        $this->aclGroupProvider = $aclGroupProvider;
    }

    /**
     * Checks whether an entity is protected using the given security type
     */
    public function isProtectedEntity(string $className, string $securityType = self::ACL_SECURITY_TYPE): bool
    {
        $this->ensureShortMetadataLoaded($securityType);

        if (isset($this->shortLocalCache[$securityType][$className])) {
            $group = $this->aclGroupProvider->getGroup();

            return !$group || $this->shortLocalCache[$securityType][$className][0] === $group;
        }

        return false;
    }

    /**
     * Checks if the given field has an alias and if so, returns it instead of the given field name
     */
    public function getProtectedFieldName(
        string $className,
        string $fieldName,
        string $securityType = self::ACL_SECURITY_TYPE
    ): string {
        $this->ensureShortMetadataLoaded($securityType);

        if (isset($this->shortLocalCache[$securityType][$className])) {
            $fields = $this->shortLocalCache[$securityType][$className][1];
            if (isset($fields[$fieldName])) {
                $fieldName = $fields[$fieldName];
            }
        }

        return $fieldName;
    }

    /**
     * Gets metadata for all entities marked with the given security type
     */
    public function getEntities(string $securityType = self::ACL_SECURITY_TYPE): array
    {
        $this->ensureMetadataLoaded($securityType);

        return array_values($this->localCache[$securityType]);
    }

    public function warmUpCache(): void
    {
        $securityTypes = [];
        $securityConfigs = $this->configManager->getConfigs('security', null, true);
        foreach ($securityConfigs as $securityConfig) {
            $securityType = $securityConfig->get('type');
            if ($securityType && !in_array($securityType, $securityTypes, true)) {
                $securityTypes[] = $securityType;
            }
        }
        foreach ($securityTypes as $securityType) {
            $this->loadMetadata($securityType, null, null);
        }
    }

    public function clearCache(): void
    {
        $this->localCache = [];
        $this->shortLocalCache = [];
        $this->cache->clear();
    }

    /**
     * Get entity metadata
     */
    public function getMetadata(
        string $className,
        string $securityType = self::ACL_SECURITY_TYPE
    ): EntitySecurityMetadata {
        $this->ensureMetadataLoaded($securityType);

        if (!isset($this->localCache[$securityType][$className])) {
            throw new \LogicException(
                sprintf('The entity "%s" must be %s protected.', $className, $securityType)
            );
        }

        return $this->localCache[$securityType][$className];
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached
     */
    private function ensureMetadataLoaded(string $securityType): void
    {
        if (!isset($this->localCache[$securityType])) {
            $fullCacheItem = $this->cache->getItem(self::FULL_CACHE_KEY_PREFIX . $securityType);
            if ($fullCacheItem->isHit()) {
                $this->localCache[$securityType] = $fullCacheItem->get();
            } else {
                $this->loadMetadata($securityType, $fullCacheItem, null);
            }
        }
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached
     */
    private function ensureShortMetadataLoaded($securityType): void
    {
        if (!isset($this->shortLocalCache[$securityType])) {
            $shortCacheItem = $this->cache->getItem(self::SHORT_CACHE_KEY_PREFIX . $securityType);
            if ($shortCacheItem->isHit()) {
                $this->shortLocalCache[$securityType] = $shortCacheItem->get();
            } else {
                $this->loadMetadata($securityType, null, $shortCacheItem);
            }
        }
    }

    /**
     * Loads metadata for the given security type and save them in cache
     */
    private function loadMetadata(
        string $securityType,
        ?CacheItemInterface $fullCacheItem,
        ?CacheItemInterface $shortCacheItem
    ): void {
        $data = [];
        $shortData = [];
        $securityConfigs = $this->configManager->getConfigs('security', null, true);
        foreach ($securityConfigs as $securityConfig) {
            $className = $securityConfig->getId()->getClassName();
            if ($this->isEntityApplicable($securityConfig, $className, $securityType)) {
                $metadata = $this->getEntityMetadata($securityConfig, $className, $securityType);
                $data[$className] = $metadata;

                $fieldAliases = [];
                foreach ($metadata->getFields() as $fieldName => $field) {
                    $fieldAlias = $field->getAlias();
                    if ($fieldAlias) {
                        $fieldAliases[$fieldName] = $fieldAlias;
                    }
                }
                $shortData[$className] = [$metadata->getGroup(), $fieldAliases];
            }
        }

        $fullCacheItem ??=$this->cache->getItem(self::FULL_CACHE_KEY_PREFIX . $securityType);
        $fullCacheItem->set($data);
        $this->cache->save($fullCacheItem);
        $shortCacheItem ??=$this->cache->getItem(self::SHORT_CACHE_KEY_PREFIX . $securityType);
        $shortCacheItem->set($shortData);
        $this->cache->save($shortCacheItem);
        $this->localCache[$securityType] = $data;
        $this->shortLocalCache[$securityType] = $shortData;
    }

    private function isEntityApplicable(ConfigInterface $securityConfig, string $className, string $securityType): bool
    {
        if ($securityConfig->get('type') !== $securityType) {
            return false;
        }

        return $this->configManager
            ->getEntityConfig('extend', $className)
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
    }

    private function getEntityMetadata(
        ConfigInterface $securityConfig,
        string $className,
        string $securityType
    ): EntitySecurityMetadata {
        $description = $securityConfig->get('description');
        if ($description) {
            $description = new Label($description);
        }

        return new EntitySecurityMetadata(
            $securityType,
            $className,
            $securityConfig->get('group_name'),
            new Label($this->configManager->getEntityConfig('entity', $className)->get('label')),
            $this->getPermissionsList($securityConfig),
            $description,
            $securityConfig->get('category'),
            $this->getFields($securityConfig, $className)
        );
    }

    /**
     * Gets an array of fields metadata
     */
    private function getFields(ConfigInterface $securityConfig, string $className): array
    {
        $fields = [];
        if ($securityConfig->get('field_acl_supported') && $securityConfig->get('field_acl_enabled')) {
            $classMetadata = $this->getClassMetadata($className);
            $fieldsConfig = $this->configManager->getConfigs('security', $className);
            foreach ($fieldsConfig as $fieldConfig) {
                $fieldName = $fieldConfig->getId()->getFieldName();
                if ($classMetadata->isIdentifier($fieldName)) {
                    // we should not limit access to identifier fields.
                    continue;
                }

                $fields[$fieldName] = new FieldSecurityMetadata(
                    $fieldName,
                    new Label($this->getFieldLabel($classMetadata, $fieldName)),
                    $this->getPermissionsList($fieldConfig)
                );
            }

            $event = new LoadFieldsMetadata($className, $fields);
            $this->eventDispatcher->dispatch($event, LoadFieldsMetadata::NAME);
            $fields = $event->getFields();
        }

        return $fields;
    }

    /**
     * Gets a label of a field
     */
    private function getFieldLabel(ClassMetadata $metadata, string $fieldName): string
    {
        $label = null;
        $className = $metadata->getName();
        if ($this->configManager->hasConfig($className, $fieldName)) {
            $label = $this->configManager->getFieldConfig('entity', $className, $fieldName)->get('label');
        }
        if (!$label) {
            $label = ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
        }

        return $label;
    }

    /**
     * Returns array with supported permissions
     */
    private function getPermissionsList(ConfigInterface $securityConfig): array #e.g. ['VIEW', 'CREATE']
    {
        $permissions = $securityConfig->get('permissions');

        return $permissions && self::ALL_PERMISSIONS !== $permissions
            ? explode(self::PERMISSIONS_DELIMITER, $permissions)
            : [];
    }

    private function getClassMetadata(string $className): ClassMetadata
    {
        $manager = $this->doctrine
            ->getManagerForClass($className);
        if ($manager === null) {
            throw new \LogicException(sprintf('There is no manager for %s', $className));
        }

        return $manager->getMetadataFactory()
            ->getMetadataFor($className);
    }
}
