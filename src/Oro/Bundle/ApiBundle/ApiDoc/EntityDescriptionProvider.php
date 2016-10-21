<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

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

    /** @var ConfigProvider */
    protected $entityConfigProvider;

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
     * @param ConfigProvider                   $entityConfigProvider
     * @param DoctrineHelper                   $doctrineHelper
     * @param TranslatorInterface              $translator
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        ConfigProvider $entityConfigProvider,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->entityConfigProvider = $entityConfigProvider;
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
        if (!isset($this->cache[$entityClass])) {
            $this->cache[$entityClass] = [];
        }
        if (array_key_exists(self::DESCRIPTION, $this->cache[$entityClass])) {
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
        if (!isset($this->cache[$entityClass])) {
            $this->cache[$entityClass] = [];
        }
        if (array_key_exists(self::PLURAL_DESCRIPTION, $this->cache[$entityClass])) {
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
        if (!isset($this->cache[$entityClass])) {
            $this->cache[$entityClass] = [];
        }
        if (array_key_exists(self::DOCUMENTATION, $this->cache[$entityClass])) {
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
        if (!isset($this->cache[$entityClass][self::FIELDS][$propertyPath])) {
            $this->cache[$entityClass][self::FIELDS][$propertyPath] = [];
        }
        if (array_key_exists(self::DESCRIPTION, $this->cache[$entityClass][self::FIELDS][$propertyPath])) {
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
        if (!isset($this->cache[$entityClass][self::FIELDS][$propertyPath])) {
            $this->cache[$entityClass][self::FIELDS][$propertyPath] = [];
        }
        if (array_key_exists(self::DOCUMENTATION, $this->cache[$entityClass][self::FIELDS][$propertyPath])) {
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
        $result = null;
        $config = $this->getEntityConfig($entityClass);
        if (null !== $config) {
            $label = $config->get('description');
            if ($label) {
                $result = $this->trans($label);
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    protected function findFieldDescription($entityClass, $propertyPath)
    {
        $result = null;
        $config = $this->findFieldConfig($entityClass, $propertyPath);
        if (null !== $config) {
            $label = $config->get('label');
            if ($label) {
                $result = $this->trans($label);
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return string|null
     */
    protected function findFieldDocumentation($entityClass, $propertyPath)
    {
        $result = null;
        $config = $this->findFieldConfig($entityClass, $propertyPath);
        if (null !== $config) {
            $label = $config->get('description');
            if ($label) {
                $result = $this->trans($label);
            }
        }

        return $result;
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
        return $this->entityConfigProvider->hasConfig($entityClass)
            ? $this->entityConfigProvider->getConfig($entityClass)
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
        return $this->entityConfigProvider->hasConfig($entityClass, $fieldName)
            ? $this->entityConfigProvider->getConfig($entityClass, $fieldName)
            : null;
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
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isManageableEntity($entityClass)
    {
        if (!array_key_exists(self::MANAGEABLE, $this->cache[$entityClass])) {
            $this->cache[$entityClass][self::MANAGEABLE] = $this->doctrineHelper->isManageableEntity($entityClass);
        }

        return $this->cache[$entityClass][self::MANAGEABLE];
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isConfigurableEntity($entityClass)
    {
        if (!array_key_exists(self::CONFIGURABLE, $this->cache[$entityClass])) {
            $this->cache[$entityClass][self::CONFIGURABLE] = $this->entityConfigProvider->hasConfig($entityClass);
        }

        return $this->cache[$entityClass][self::CONFIGURABLE];
    }
}
