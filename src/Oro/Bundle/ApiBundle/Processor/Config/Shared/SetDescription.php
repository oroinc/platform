<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\FieldConfigInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class SetDescription implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $entityConfigProvider)
    {
        $this->doctrineHelper       = $doctrineHelper;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param string               $entityClass
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     *
     * @return ConfigInterface|null
     */
    protected function findFieldConfig($entityClass, $fieldName, FieldConfigInterface $field)
    {
        if (!$field->hasPropertyPath()) {
            return $this->getFieldConfig($entityClass, $fieldName);
        }

        $path = ConfigUtil::explodePropertyPath($field->getPropertyPath());
        if (count($path) === 1) {
            return $this->getFieldConfig($entityClass, reset($path));
        }

        $linkedProperty = array_pop($path);
        $classMetadata  = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);

        return null !== $classMetadata
            ? $this->getFieldConfig($classMetadata->name, $linkedProperty)
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
}
