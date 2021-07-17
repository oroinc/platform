<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
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

    /** @var ConfigManager */
    private $configManager;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var CacheProvider */
    private $cache;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var AclGroupProviderInterface */
    private $aclGroupProvider;

    /** @var array [security type => [class name => EntitySecurityMetadata, ...], ...] */
    private $localCache = [];

    /** @var array [security type => [class name => [group, [field name => field alias, ...]], ...], ...] */
    private $shortLocalCache = [];

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        CacheProvider $cache,
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
     * Checks whether an entity is protected using the given security type.
     *
     * @param string $className    The entity class name
     * @param string $securityType The security type. Defaults to ACL.
     *
     * @return bool
     */
    public function isProtectedEntity($className, $securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureShortMetadataLoaded($securityType);

        if (isset($this->shortLocalCache[$securityType][$className])) {
            $group = $this->aclGroupProvider->getGroup();

            return !$group || $this->shortLocalCache[$securityType][$className][0] === $group;
        }

        return false;
    }

    /**
     * Checks if the given field has an alias and if so, returns it instead of the given field name.
     *
     * @param string $className    The entity class name
     * @param string $fieldName    The field name
     * @param string $securityType The security type. Defaults to ACL.
     *
     * @return string
     */
    public function getProtectedFieldName($className, $fieldName, $securityType = self::ACL_SECURITY_TYPE)
    {
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
     * Gets metadata for all entities marked with the given security type.
     *
     * @param string $securityType The security type. Defaults to ACL.
     *
     * @return EntitySecurityMetadata[]
     */
    public function getEntities($securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);

        return array_values($this->localCache[$securityType]);
    }

    /**
     * {@inheritdoc}
     */
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
            $this->loadMetadata($securityType);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->localCache = [];
        $this->shortLocalCache = [];
        $this->cache->deleteAll();
    }

    /**
     * Get entity metadata.
     *
     * @param string $className
     * @param string $securityType
     *
     * @return EntitySecurityMetadata
     */
    public function getMetadata($className, $securityType = self::ACL_SECURITY_TYPE)
    {
        $this->ensureMetadataLoaded($securityType);

        if (!isset($this->localCache[$securityType][$className])) {
            throw new \LogicException(sprintf('The entity "%s" must be %s protected.', $className, $securityType));
        }

        return $this->localCache[$securityType][$className];
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached.
     *
     * @param string $securityType The security type.
     */
    private function ensureMetadataLoaded($securityType)
    {
        if (!isset($this->localCache[$securityType])) {
            $data = $this->cache->fetch(self::FULL_CACHE_KEY_PREFIX . $securityType);
            if (false !== $data) {
                $this->localCache[$securityType] = $data;
            } else {
                $this->loadMetadata($securityType);
            }
        }
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached.
     *
     * @param string $securityType The security type.
     */
    private function ensureShortMetadataLoaded($securityType)
    {
        if (!isset($this->shortLocalCache[$securityType])) {
            $data = $this->cache->fetch(self::SHORT_CACHE_KEY_PREFIX . $securityType);
            if (false !== $data) {
                $this->shortLocalCache[$securityType] = $data;
            } else {
                $this->loadMetadata($securityType);
            }
        }
    }

    /**
     * Loads metadata for the given security type and save them in cache.
     *
     * @param string $securityType
     */
    private function loadMetadata($securityType)
    {
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

        $this->cache->save(self::FULL_CACHE_KEY_PREFIX . $securityType, $data);
        $this->cache->save(self::SHORT_CACHE_KEY_PREFIX . $securityType, $shortData);
        $this->localCache[$securityType] = $data;
        $this->shortLocalCache[$securityType] = $shortData;
    }

    /**
     * @param ConfigInterface $securityConfig
     * @param string          $className
     * @param string          $securityType
     *
     * @return bool
     */
    private function isEntityApplicable(ConfigInterface $securityConfig, $className, $securityType)
    {
        if ($securityConfig->get('type') !== $securityType) {
            return false;
        }

        return $this->configManager
            ->getEntityConfig('extend', $className)
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
    }

    /**
     * @param ConfigInterface $securityConfig
     * @param string          $className
     * @param string          $securityType
     *
     * @return EntitySecurityMetadata
     */
    private function getEntityMetadata(ConfigInterface $securityConfig, $className, $securityType)
    {
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
     * Gets an array of fields metadata.
     *
     * @param        $securityConfig
     * @param string $className
     *
     * @return FieldSecurityMetadata[]
     */
    private function getFields(ConfigInterface $securityConfig, $className)
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
     * Gets a label of a field.
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return string
     */
    private function getFieldLabel(ClassMetadata $metadata, $fieldName)
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
     * Returns array with supported permissions.
     *
     * @param ConfigInterface $securityConfig
     *
     * @return string[] Array with permissions, e.g. ['VIEW', 'CREATE']
     */
    private function getPermissionsList(ConfigInterface $securityConfig)
    {
        $permissions = $securityConfig->get('permissions');

        return $permissions && self::ALL_PERMISSIONS !== $permissions
            ? explode(self::PERMISSIONS_DELIMITER, $permissions)
            : [];
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    private function getClassMetadata($className)
    {
        return $this->doctrine
            ->getManagerForClass($className)
            ->getMetadataFactory()
            ->getMetadataFor($className);
    }
}
