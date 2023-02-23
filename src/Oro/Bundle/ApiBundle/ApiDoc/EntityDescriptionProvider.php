<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extracts descriptions in English for entities and fields from entity configs.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityDescriptionProvider
{
    private const DESCRIPTION = 'description';
    private const PLURAL_DESCRIPTION = 'plural_description';
    private const DOCUMENTATION = 'documentation';
    private const MANAGEABLE = 'manageable';
    private const CONFIGURABLE = 'configurable';
    private const FIELDS = 'fields';

    private const SCOPE_ENTITY = 'entity';
    private const ATTR_DESCRIPTION = 'description';
    private const ATTR_LABEL = 'label';

    private EntityClassNameProviderInterface $entityClassNameProvider;
    private ConfigManager $configManager;
    private DoctrineHelper $doctrineHelper;
    private TranslatorInterface $translator;

    /**
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
    private array $cache = [];

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
     */
    public function getEntityDescription(string $entityClass): ?string
    {
        if ($this->hasEntityAttribute($entityClass, self::DESCRIPTION)) {
            return $this->cache[$entityClass][self::DESCRIPTION];
        }

        $result = $this->entityClassNameProvider->getEntityClassName($entityClass);
        if (null === $result) {
            $result = $this->trans(EntityLabelBuilder::getEntityLabelTranslationKey($entityClass));
        }
        $this->cache[$entityClass][self::DESCRIPTION] = $result;

        return $result;
    }

    /**
     * Returns the human-readable plural description in English of the given entity type.
     */
    public function getEntityPluralDescription(string $entityClass): ?string
    {
        if ($this->hasEntityAttribute($entityClass, self::PLURAL_DESCRIPTION)) {
            return $this->cache[$entityClass][self::PLURAL_DESCRIPTION];
        }

        $result = $this->entityClassNameProvider->getEntityClassPluralName($entityClass);
        if (null === $result) {
            $result = $this->trans(EntityLabelBuilder::getEntityPluralLabelTranslationKey($entityClass));
        }
        $this->cache[$entityClass][self::PLURAL_DESCRIPTION] = $result;

        return $result;
    }

    /**
     * Returns the detailed documentation in English of the given entity type.
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
     */
    public function getFieldDescription(string $entityClass, string $propertyPath): ?string
    {
        if ($this->hasFieldAttribute($entityClass, $propertyPath, self::DESCRIPTION)) {
            return $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DESCRIPTION];
        }

        $result = null;
        if ($this->isManageableEntity($entityClass)) {
            $result = $this->findFieldDescription($entityClass, $propertyPath);
        }
        if (!$result && !str_contains($propertyPath, '.')) {
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
     */
    public function getFieldDocumentation(string $entityClass, string $propertyPath): ?string
    {
        if ($this->hasFieldAttribute($entityClass, $propertyPath, self::DOCUMENTATION)) {
            return $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DOCUMENTATION];
        }

        $result = null;
        if ($this->isManageableEntity($entityClass)) {
            $result = $this->findFieldDocumentation($entityClass, $propertyPath);
        }
        $this->cache[$entityClass][self::FIELDS][$propertyPath][self::DOCUMENTATION] = $result;

        return $result;
    }

    /**
     * Returns the human-readable description in English of the given association name.
     */
    public function humanizeAssociationName(string $associationName): string
    {
        return $this->humanizePropertyName($associationName);
    }

    private function findEntityDocumentation(string $entityClass): ?string
    {
        $entityConfig = $this->getEntityConfig($entityClass);
        if (null !== $entityConfig) {
            return $this->transConfigAttribute(self::ATTR_DESCRIPTION, $entityConfig);
        }

        return $this->trans(EntityLabelBuilder::getEntityDescriptionTranslationKey($entityClass));
    }

    private function findFieldDescription(string $entityClass, string $propertyPath): ?string
    {
        $fieldInfo = $this->getFieldInfo($entityClass, $propertyPath);
        if (null === $fieldInfo) {
            return null;
        }
        [$entityClass, $propertyPath] = $fieldInfo;

        $fieldConfig = $this->getFieldConfig($entityClass, $propertyPath);
        if (null !== $fieldConfig) {
            return $this->transConfigAttribute(self::ATTR_LABEL, $fieldConfig);
        }

        return $this->trans(EntityLabelBuilder::getFieldLabelTranslationKey($entityClass, $propertyPath));
    }

    private function findFieldDocumentation(string $entityClass, string $propertyPath): ?string
    {
        $fieldInfo = $this->getFieldInfo($entityClass, $propertyPath);
        if (null === $fieldInfo) {
            return null;
        }
        [$entityClass, $propertyPath] = $fieldInfo;

        $fieldConfig = $this->getFieldConfig($entityClass, $propertyPath);
        if (null !== $fieldConfig) {
            return $this->transConfigAttribute(self::ATTR_DESCRIPTION, $fieldConfig);
        }

        return $this->trans(EntityLabelBuilder::getFieldDescriptionTranslationKey($entityClass, $propertyPath));
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return array|null [entity class, field name]
     */
    private function getFieldInfo(string $entityClass, string $propertyPath): ?array
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        if (\count($path) === 1) {
            return [$entityClass, reset($path)];
        }

        $linkedProperty = array_pop($path);
        $classMetadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);
        if (null === $classMetadata) {
            return null;
        }

        return [$classMetadata->name, $linkedProperty];
    }

    private function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        return $this->isConfigurableEntity($entityClass)
            ? $this->configManager->getEntityConfig(self::SCOPE_ENTITY, $entityClass)
            : null;
    }

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

    private function humanizePropertyName(string $propertyPath): string
    {
        $result = strtr($propertyPath, ['_' => ' ', '-' => ' ']);
        $result = ' ' . preg_replace('/(?<=[^A-Z ])([A-Z])/', ' $1', $result);
        $result = preg_replace_callback(
            '/([A-Z])([A-Z])([a-z])/',
            static fn (array $matches) => $matches[1] . ' ' . strtolower($matches[2]) . $matches[3],
            $result
        );
        $result = preg_replace_callback(
            '/ ([A-Z])([^A-Z])/',
            static fn (array $matches) => ' ' . strtolower($matches[1]) . $matches[2],
            $result
        );

        return ltrim($result, ' ');
    }

    private function trans(string $label): ?string
    {
        $translated = $this->translator->trans($label);

        return !empty($translated) && $translated !== $label
            ? $translated
            : null;
    }

    private function transConfigAttribute(string $attributeName, ConfigInterface $config): ?string
    {
        $label = $config->get($attributeName);
        if (!$label) {
            return null;
        }

        return $this->trans($label);
    }

    private function isManageableEntity(string $entityClass): bool
    {
        if ($this->hasEntityAttribute($entityClass, self::MANAGEABLE)) {
            return $this->cache[$entityClass][self::MANAGEABLE];
        }

        $result = $this->doctrineHelper->isManageableEntity($entityClass);
        $this->cache[$entityClass][self::MANAGEABLE] = $result;

        return $result;
    }

    private function isConfigurableEntity(string $entityClass): bool
    {
        if ($this->hasEntityAttribute($entityClass, self::CONFIGURABLE)) {
            return $this->cache[$entityClass][self::CONFIGURABLE];
        }

        $result = $this->configManager->hasConfig($entityClass) && !$this->configManager->isHiddenModel($entityClass);
        $this->cache[$entityClass][self::CONFIGURABLE] = $result;

        return $result;
    }

    private function hasEntityAttribute(string $entityClass, string $attributeName): bool
    {
        return
            isset($this->cache[$entityClass])
            && \array_key_exists($attributeName, $this->cache[$entityClass]);
    }

    private function hasFieldAttribute(string $entityClass, string $propertyPath, string $attributeName): bool
    {
        return
            isset($this->cache[$entityClass][self::FIELDS][$propertyPath])
            && \array_key_exists($attributeName, $this->cache[$entityClass][self::FIELDS][$propertyPath]);
    }
}
