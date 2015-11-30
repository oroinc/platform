<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SetDescriptionForFields implements ProcessorInterface
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)
            || empty($definition[ConfigUtil::FIELDS])
            || !is_array($definition[ConfigUtil::FIELDS])
        ) {
            // a configuration of fields does not exist or a description is not needed
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }


        $fields = array_keys($definition[ConfigUtil::FIELDS]);
        foreach ($fields as $fieldName) {
            $fieldConfig = $definition[ConfigUtil::FIELDS][$fieldName];
            $config      = $this->findFieldConfig($entityClass, $fieldName, $fieldConfig);
            if (null !== $config) {
                if (null === $fieldConfig) {
                    $fieldConfig = [];
                }
                if (!isset($fieldConfig[ConfigUtil::LABEL])) {
                    $fieldConfig[ConfigUtil::LABEL] = new Label($config->get('label'));
                }
                if (!isset($fieldConfig[ConfigUtil::DESCRIPTION])) {
                    $fieldConfig[ConfigUtil::DESCRIPTION] = new Label($config->get('description'));
                }
                $definition[ConfigUtil::FIELDS][$fieldName] = $fieldConfig;
            }
        }

        $context->setResult($definition);
    }

    /**
     * @param string     $entityClass
     * @param string     $fieldName
     * @param array|null $fieldConfig
     *
     * @return ConfigInterface|null
     */
    protected function findFieldConfig($entityClass, $fieldName, $fieldConfig)
    {
        if (null === $fieldConfig || !isset($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
            return $this->getFieldConfig($entityClass, $fieldName);
        }

        $path = ConfigUtil::explodePropertyPath($fieldConfig[ConfigUtil::PROPERTY_PATH]);
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
