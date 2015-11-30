<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class CompleteSorters implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $sorters = $context->getSorters();
        if (empty($sorters)) {
            // nothing to normalize
            return;
        }

        $fields = ConfigUtil::getArrayValue($sorters, ConfigUtil::FIELDS);

        if (ConfigUtil::isExcludeAll($sorters)) {
            $fields = ConfigUtil::removeExclusions($sorters);
        } else {
            $entityClass = $context->getClassName();
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $fields = ConfigUtil::removeExclusions(
                    $this->completeSorters($fields, $entityClass, $context->getResult())
                );
            }
        }

        $context->setSorters(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                ConfigUtil::FIELDS           => $fields
            ]
        );
    }

    /**
     * @param array      $sorters
     * @param string     $entityClass
     * @param array|null $config
     *
     * @return array
     */
    protected function completeSorters(array $sorters, $entityClass, $config)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $fields = array_merge(
            array_keys($this->doctrineHelper->getIndexedFields($metadata)),
            array_keys($this->doctrineHelper->getIndexedAssociations($metadata))
        );
        foreach ($fields as $fieldName) {
            if (array_key_exists($fieldName, $sorters)) {
                // already defined
                continue;
            }
            $sorters[$fieldName] = null;
        }

        if (!empty($config)) {
            foreach ($sorters as $fieldName => &$fieldConfig) {
                if (ConfigUtil::isExcludedField($config, $fieldName)) {
                    $fieldConfig[ConfigUtil::EXCLUDE] = true;
                }
            }
        }

        return $sorters;
    }
}
