<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class that provides possibility to collect and receive metadata for classes and their fields by security type
 */
class EntitySecurityMetadataProvider
{
    const ACL_SECURITY_TYPE = 'ACL';
    const ALL_PERMISSIONS = 'All';
    const PERMISSIONS_DELIMITER = ';';

    /** @var ConfigProvider */
    protected $securityConfigProvider;

    /**  @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /**  @var CacheProvider */
    protected $cache;

    /**
     * @var array
     *     key = security type
     *     value = array
     *         key = class name
     *         value = EntitySecurityMetadata
     */
    protected $localCache = [];

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param ConfigProvider      $securityConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     * @param CacheProvider|null  $cache
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigProvider $securityConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        CacheProvider  $cache = null,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->securityConfigProvider = $securityConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
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
        $this->ensureMetadataLoaded($securityType);

        return isset($this->localCache[$securityType][$className]);
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
        $this->translateMetadata($this->localCache[$securityType]);

        return array_values($this->localCache[$securityType]);
    }

    /**
     * Warms up the cache.
     */
    public function warmUpCache()
    {
        $securityTypes = [];
        foreach ($this->securityConfigProvider->getConfigs() as $securityConfig) {
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
     * Clears the cache by security type.
     *
     * If the $securityType is not specified, clear all cached data
     *
     * @param string|null $securityType The security type.
     */
    public function clearCache($securityType = null)
    {
        if ($this->cache) {
            if ($securityType !== null) {
                $this->cache->delete($securityType);
            } else {
                $this->cache->deleteAll();
            }
        }
        if ($securityType !== null) {
            unset($this->localCache[$securityType]);
        } else {
            $this->localCache = [];
        }
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

        $result = $this->localCache[$securityType][$className];
        if ($result === true) {
            return new EntitySecurityMetadata();
        }
        $this->translateMetadata([$result]);

        return $result;
    }

    /**
     * Makes sure that metadata for the given security type are loaded and cached.
     *
     * @param string $securityType The security type.
     */
    protected function ensureMetadataLoaded($securityType)
    {
        if (!isset($this->localCache[$securityType])) {
            $data = null;
            if ($this->cache) {
                $data = $this->cache->fetch($securityType);
            }
            if ($data) {
                $this->localCache[$securityType] = $data;
            } else {
                $this->loadMetadata($securityType);
            }
        }
    }

    /**
     * Loads metadata for the given security type and save them in cache.
     *
     * @param $securityType
     */
    protected function loadMetadata($securityType)
    {
        $data = [];

        $securityConfigs = $this->securityConfigProvider->getConfigs(null, true);

        foreach ($securityConfigs as $securityConfig) {
            $className = $securityConfig->getId()->getClassName();

            if ($securityConfig->get('type') === $securityType
                && $this->extendConfigProvider->getConfig($className)->in(
                    'state',
                    [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
                )
            ) {
                $label = $this->entityConfigProvider->getConfig($className)->get('label');
                $description = $securityConfig->get('description');
                $permissions = $this->getPermissionsList($securityConfig);

                $data[$className] = new EntitySecurityMetadata(
                    $securityType,
                    $className,
                    $securityConfig->get('group_name'),
                    $label,
                    $permissions,
                    $description,
                    $securityConfig->get('category'),
                    $this->getFields($securityConfig, $className)
                );
            }
        }

        if ($this->cache) {
            $this->cache->save($securityType, $data);
        }

        $this->localCache[$securityType] = $data;
    }

    /**
     * Gets an array of fields metadata.
     *
     * @param $securityConfig
     * @param string $className
     *
     * @return array|FieldSecurityMetadata[]
     */
    protected function getFields(ConfigInterface $securityConfig, $className)
    {
        $fields = [];
        if ($securityConfig->get('field_acl_supported') && $securityConfig->get('field_acl_enabled')) {
            $fieldsConfig = $this->securityConfigProvider->getConfigs($className);
            $classMetadata = $this->doctrine
                ->getManagerForClass($className)
                ->getMetadataFactory()
                ->getMetadataFor($className);

            foreach ($fieldsConfig as $fieldConfig) {
                $fieldName = $fieldConfig->getId()->getFieldName();
                if ($classMetadata->isIdentifier($fieldName)) {
                    // we should not limit access to identifier fields.
                    continue;
                }
                $permissions = $this->getPermissionsList($fieldConfig);

                $fields[$fieldName] = new FieldSecurityMetadata(
                    $fieldName,
                    $this->getFieldLabel($classMetadata, $fieldName),
                    $permissions
                );
            }

            $event = new LoadFieldsMetadata($className, $fields);
            $this->eventDispatcher->dispatch(LoadFieldsMetadata::NAME, $event);
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
    protected function getFieldLabel(ClassMetadata $metadata, $fieldName)
    {
        $className = $metadata->getName();
        if (!$metadata->hasField($fieldName) && !$metadata->hasAssociation($fieldName)) {
            // virtual field or relation
            return ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
        }

        $label = $this->entityConfigProvider->hasConfig($className, $fieldName)
            ? $this->entityConfigProvider->getConfig($className, $fieldName)->get('label')
            : null;

        return !empty($label)
            ? $label
            : ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
    }

    /**
     * Returns array with supported permissions.
     *
     * @param ConfigInterface $securityConfig
     *
     * @return array|null Array with permissions, f.e. ['VIEW', 'CREATE']
     */
    protected function getPermissionsList(ConfigInterface $securityConfig)
    {
        $permissions = $securityConfig->get('permissions');

        if (!$permissions || $permissions === self::ALL_PERMISSIONS) {
            $permissions = [];
        } else {
            $permissions = explode(self::PERMISSIONS_DELIMITER, $permissions);
        }

        return $permissions;
    }

    /**
     * @param array|EntitySecurityMetadata[] $classMetadataArray
     */
    protected function translateMetadata(array $classMetadataArray)
    {
        foreach ($classMetadataArray as $classMetadata) {
            if ($classMetadata->isTranslated()) {
                continue;
            }

            if ($classMetadata->getLabel()) {
                $classMetadata->setLabel($this->translator->trans($classMetadata->getLabel()));
            }
            if ($classMetadata->getDescription()) {
                $classMetadata->setDescription($this->translator->trans($classMetadata->getDescription()));
            }

            $fieldMetadataArray = $classMetadata->getFields();
            if (!$fieldMetadataArray) {
                continue;
            }

            foreach ($fieldMetadataArray as $fieldMetadata) {
                if (!$fieldMetadata->getLabel()) {
                    continue;
                }
                $fieldMetadata->setLabel($this->translator->trans($fieldMetadata->getLabel()));
            }

            uasort($fieldMetadataArray, function (FieldSecurityMetadata $a, FieldSecurityMetadata $b) {
                return strcmp($a->getLabel(), $b->getLabel());
            });
            $classMetadata->setFields($fieldMetadataArray);
            $classMetadata->setTranslated(true);
        }
    }
}
