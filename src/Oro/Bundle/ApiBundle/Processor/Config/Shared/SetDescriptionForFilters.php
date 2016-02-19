<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SetDescriptionForFilters implements ProcessorInterface
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

        $filters = $context->getFilters();
        if (empty($filters) || empty($filters[ConfigUtil::FIELDS])) {
            // a configuration of filters does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }


        foreach ($filters[ConfigUtil::FIELDS] as $filterKey => &$filterConfig) {
            if (!isset($filterConfig[ConfigUtil::DESCRIPTION])) {
                $config = $this->findFieldConfig($entityClass, $filterKey, $filterConfig);
                if (null !== $config) {
                    $filterConfig[ConfigUtil::DESCRIPTION] = new Label($config->get('label'));
                }
            }
        }

        $context->setFilters($filters);
    }

    /**
     * @param string $entityClass
     * @param string $filterKey
     * @param array  $filterConfig
     *
     * @return ConfigInterface|null
     */
    protected function findFieldConfig($entityClass, $filterKey, $filterConfig)
    {
        $path = ConfigUtil::explodePropertyPath(
            ConfigUtil::getPropertyPath($filterConfig, $filterKey)
        );
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
