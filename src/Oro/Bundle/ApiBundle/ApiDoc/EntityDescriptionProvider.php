<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Extracts descriptions in English for entities and fields from entity configs.
 */
class EntityDescriptionProvider
{
    private const DESCRIPTION        = 'description';
    private const PLURAL_DESCRIPTION = 'plural_description';
    private const DOCUMENTATION      = 'documentation';
    private const MANAGEABLE         = 'manageable';
    private const CONFIGURABLE       = 'configurable';
    private const FIELDS             = 'fields';

    private const SCOPE_ENTITY     = 'entity';
    private const ATTR_DESCRIPTION = 'description';
    private const ATTR_LABEL       = 'label';

    /** @var EntityClassNameProviderInterface */
    private $entityClassNameProvider;

    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TranslatorInterface */
    private $translator;

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
    private $cache = [];

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
    public function getEntityDescription(string $entityClass): ?string
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
    public function getEntityPluralDescription(string $entityClass): ?string
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
    public function getEntityDocumentation(string $entityClass): ?string
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
    public function getFieldDescription(string $entityClass, string $propertyPath): ?string
    {
        if ($this->hasFieldAttribute($entityClass, $propertyPath, self::DESCRIPTION)) {
            return $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DESCRIPTION];
        }

        $result = null;
        if ($this->isManageableEntity($entityClass) && $this->isConfigurableEntity($entityClass)) {
            $result = $this->findFieldDescription($entityClass, $propertyPath);
        }
        if (!$result && false === \strpos($propertyPath, '.')) {
            $result = $this->humanizePropertyName($propertyPath);
        }
        if ($result) {
            $result = \strtolower($result);
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
    public function getFieldDocumentation(string $entityClass, string $propertyPath): ?string
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
    public function humanizeAssociationName(string $associationName): string
    {
        return $this->humanizePropertyName($associationName);
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    private function findEntityDocumentation(string $entityClass): ?string
    {
        return $this->transConfigAttribute(
            self::ATTR_DESCRIPTION,
            $this->getEntityConfig($entityClass)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    private function findFieldDescription(string $entityClass, string $propertyPath): ?string
    {
        return $this->transConfigAttribute(
            self::ATTR_LABEL,
            $this->findFieldConfig($entityClass, $propertyPath)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    private function findFieldDocumentation(string $entityClass, string $propertyPath): ?string
    {
        return $this->transConfigAttribute(
            self::ATTR_DESCRIPTION,
            $this->findFieldConfig($entityClass, $propertyPath)
        );
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return ConfigInterface|null
     */
    private function findFieldConfig(string $entityClass, string $propertyPath): ?ConfigInterface
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        if (\count($path) === 1) {
            return $this->getFieldConfig($entityClass, \reset($path));
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
    private function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        return $this->isConfigurableEntity($entityClass)
            ? $this->configManager->getEntityConfig(self::SCOPE_ENTITY, $entityClass)
            : null;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return ConfigInterface|null
     */
    private function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        if (!$this->isConfigurableEntity($entityClass)
            || !$this->configManager->hasConfig($entityClass, $fieldName)
            || $this->configManager->isHiddenModel($entityClass, $fieldName)
        ) {
            return null;
        }

        return $this->configManager->getFieldConfig(self::SCOPE_ENTITY, $entityClass, $fieldName);
    }

    /**
     * @param string $propertyPath
     *
     * @return string
     */
    private function humanizePropertyName(string $propertyPath): string
    {
        return \preg_replace(
            '/(?<=[^A-Z])([A-Z])/',
            ' $1',
            \strtr($propertyPath, ['_' => ' ', '-' => ' '])
        );
    }

    /**
     * @param string $label
     *
     * @return string|null
     */
    private function trans(string $label): ?string
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
    private function transConfigAttribute(string $attributeName, ConfigInterface $config = null): ?string
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
    private function isManageableEntity(string $entityClass): bool
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
    private function isConfigurableEntity(string $entityClass): bool
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
    private function hasEntityAttribute(string $entityClass, string $attributeName): bool
    {
        return
            isset($this->cache[$entityClass])
            && \array_key_exists($attributeName, $this->cache[$entityClass]);
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     * @param string $attributeName
     *
     * @return bool
     */
    private function hasFieldAttribute(string $entityClass, string $propertyPath, string $attributeName): bool
    {
        return
            isset($this->cache[$entityClass][self::FIELDS][$propertyPath])
            && \array_key_exists($attributeName, $this->cache[$entityClass][self::FIELDS][$propertyPath]);
    }
}
