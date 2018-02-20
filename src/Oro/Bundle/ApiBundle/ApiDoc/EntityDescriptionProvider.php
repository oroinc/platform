<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Translation\TranslatorInterface;

class EntityDescriptionProvider
{
    const DESCRIPTION        = 'description';
    const PLURAL_DESCRIPTION = 'plural_description';
    const DOCUMENTATION      = 'documentation';
    const MANAGEABLE         = 'manageable';
    const CONFIGURABLE       = 'configurable';
    const FIELDS             = 'fields';

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var ConfigManager */
    protected $configManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @var array
     *  [
     *      entity class => [
     *          'description'        => entity description,
     *          'plural_description' => entity plural description,
     *          'manageable'         => whether it is ORM entity,
     *          'configurable'       => whether has entity configs,
     *          'fields'             => [
     *              property path => description
     *          ]
     *      ],
     *      ...
     *  ]
     */
    protected $cache = [];

    /**
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param ConfigManager                    $configManager
     * @param DoctrineHelper                   $doctrineHelper
     * @param TranslatorInterface              $translator
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * Returns the human-readable description in English of the given entity type.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getEntityDescription($entityClass)
    {
        if ($this->hasEntityAttribute($entityClass, self::DESCRIPTION)) {
            return $this->cache[$entityClass][self::DESCRIPTION];
        }

        $result = $this->entityClassNameProvider->getEntityClassName($entityClass);
        $this->cache[$entityClass][self::DESCRIPTION] = $result;

        return $result;
    }

    /**
     * Returns the human-readable plural description in English of the given entity type.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getEntityPluralDescription($entityClass)
    {
        if ($this->hasEntityAttribute($entityClass, self::PLURAL_DESCRIPTION)) {
            return $this->cache[$entityClass][self::PLURAL_DESCRIPTION];
        }

        $result = $this->entityClassNameProvider->getEntityClassPluralName($entityClass);
        $this->cache[$entityClass][self::PLURAL_DESCRIPTION] = $result;

        return $result;
    }

    /**
     * Returns the detailed documentation in English of the given entity type.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getEntityDocumentation($entityClass)
    {
        if ($this->hasEntityAttribute($entityClass, self::DOCUMENTATION)) {
            return $this->cache[$entityClass][self::DOCUMENTATION];
        }

        $result = $this->findEntityDocumentation($entityClass);
        $this->cache[$entityClass][self::DOCUMENTATION] = $result;

        return $result;
    }

    /**
     * Returns the human-readable description in English of the given entity field.
     *
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function getFieldDescription($entityClass, $propertyPath)
    {
        if ($this->hasFieldAttribute($entityClass, $propertyPath, self::DESCRIPTION)) {
            return $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DESCRIPTION];
        }

        $result = null;
        if ($this->isManageableEntity($entityClass) && $this->isConfigurableEntity($entityClass)) {
            $result = $this->findFieldDescription($entityClass, $propertyPath);
        }
        if (!$result && false === strpos($propertyPath, '.')) {
            $result = $this->humanizePropertyName($propertyPath);
        }
        if ($result) {
            $result = strtolower($result);
        }

        $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DESCRIPTION] = $result;

        return $result;
    }

    /**
     * Returns the detailed documentation in English of the given entity field.
     *
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function getFieldDocumentation($entityClass, $propertyPath)
    {
        if ($this->hasFieldAttribute($entityClass, $propertyPath, self::DOCUMENTATION)) {
            return $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DOCUMENTATION];
        }

        $result = null;
        if ($this->isManageableEntity($entityClass) && $this->isConfigurableEntity($entityClass)) {
            $result = $this->findFieldDocumentation($entityClass, $propertyPath);
        }

        $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DOCUMENTATION] = $result;

        return $result;
    }

    /**
     * Returns the human-readable description in English of the given association name.
     *
     * @param string $associationName
     *
     * @return string
     */
    public function humanizeAssociationName($associationName)
    {
        return $this->humanizePropertyName($associationName);
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function findEntityDocumentation($entityClass)
    {
        return $this->transConfigAttribute(
            'description',
            $this->getEntityConfig($entityClass)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    protected function findFieldDescription($entityClass, $propertyPath)
    {
        return $this->transConfigAttribute(
            'label',
            $this->findFieldConfig($entityClass, $propertyPath)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    protected function findFieldDocumentation($entityClass, $propertyPath)
    {
        return $this->transConfigAttribute(
            'description',
            $this->findFieldConfig($entityClass, $propertyPath)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return ConfigInterface|null
     */
    protected function findFieldConfig($entityClass, $propertyPath)
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        if (count($path) === 1) {
            return $this->getFieldConfig($entityClass, reset($path));
        }

        $linkedProperty = array_pop($path);
        $classMetadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);

        return null !== $classMetadata
            ? $this->getFieldConfig($classMetadata->name, $linkedProperty)
            : null;
    }

    /**
     * @param string $entityClass
     *
     * @return ConfigInterface|null
     */
    protected function getEntityConfig($entityClass)
    {
        return $this->isConfigurableEntity($entityClass)
            ? $this->configManager->getEntityConfig('entity', $entityClass)
            : null;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return ConfigInterface|null
     */
    protected function getFieldConfig($entityClass, $fieldName)
    {
        if (!$this->isConfigurableEntity($entityClass)
            || !$this->configManager->hasConfig($entityClass, $fieldName)
            || $this->configManager->isHiddenModel($entityClass, $fieldName)
        ) {
            return null;
        }

        return $this->configManager->getFieldConfig('entity', $entityClass, $fieldName);
    }

    /**
     * @param string $propertyPath
     *
     * @return string
     */
    protected function humanizePropertyName($propertyPath)
    {
        return preg_replace(
            '/(?<=[^A-Z])([A-Z])/',
            ' $1',
            strtr($propertyPath, ['_' => ' ', '-' => ' '])
        );
    }

    /**
     * @param string $label
     *
     * @return string|null
     */
    protected function trans($label)
    {
        $translated = $this->translator->trans($label);

        return !empty($translated) && $translated !== $label
            ? $translated
            : null;
    }

    /**
     * @param string               $attributeName
     * @param ConfigInterface|null $config
     *
     * @return string|null
     */
    protected function transConfigAttribute($attributeName, ConfigInterface $config = null)
    {
        if (null === $config) {
            return null;
        }

        $label = $config->get($attributeName);
        if (!$label) {
            return null;
        }

        return $this->trans($label);
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isManageableEntity($entityClass)
    {
        if ($this->hasEntityAttribute($entityClass, self::MANAGEABLE)) {
            return $this->cache[$entityClass][self::MANAGEABLE];
        }

        $result = $this->doctrineHelper->isManageableEntity($entityClass);
        $this->cache[$entityClass][self::MANAGEABLE] = $result;

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isConfigurableEntity($entityClass)
    {
        if ($this->hasEntityAttribute($entityClass, self::CONFIGURABLE)) {
            return $this->cache[$entityClass][self::CONFIGURABLE];
        }

        $result = $this->configManager->hasConfig($entityClass) && !$this->configManager->isHiddenModel($entityClass);
        $this->cache[$entityClass][self::CONFIGURABLE] = $result;

        return $result;
    }

    /**
     * @param string $entityClass
     * @param string $attributeName
     *
     * @return bool
     */
    protected function hasEntityAttribute($entityClass, $attributeName)
    {
        return
            isset($this->cache[$entityClass])
            && array_key_exists($attributeName, $this->cache[$entityClass]);
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     * @param string $attributeName
     *
     * @return bool
     */
    protected function hasFieldAttribute($entityClass, $propertyPath, $attributeName)
    {
        return
            isset($this->cache[$entityClass][self::FIELDS][$propertyPath])
            && array_key_exists($attributeName, $this->cache[$entityClass][self::FIELDS][$propertyPath]);
    }
}
