<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Collection\Criteria;
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
            $fields = $this->removeExclusions($sorters);
        } else {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $fields = $this->removeExclusions(
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
            array_keys($this->doctrineHelper->getIndexedRelations($metadata))
        );
        foreach ($fields as $fieldName) {
            if (array_key_exists($fieldName, $sorters)) {
                // already defined
                continue;
            }
            $sorters[$fieldName] = [];
        }

        if (!empty($config)) {
            foreach ($sorters as $fieldName => &$fieldConfig) {
                if ($this->isExcludedField($config, $fieldName)) {
                    $fieldConfig[ConfigUtil::EXCLUDE] = true;
                }
            }
        }

        return $sorters;
    }

    /**
     * @param array $sorters
     *
     * @return array
     */
    protected function removeExclusions(array $sorters)
    {
        return array_filter(
            $sorters,
            function (array $config) {
                return !ConfigUtil::isExclude($config);
            }
        );
    }

    /**
     * @param array  $config
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isExcludedField(array $config, $fieldName)
    {
        $result = false;
        if (isset($config[ConfigUtil::FIELDS])) {
            $fields = $config[ConfigUtil::FIELDS];
            if (!array_key_exists($fieldName, $fields)) {
                $result = true;
            } else {
                $fieldConfig = $fields[$fieldName];
                if (is_array($fieldConfig)) {
                    if (array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
                        $fieldConfig = $fieldConfig[ConfigUtil::DEFINITION];
                    }
                    if (is_array($fieldConfig) && ConfigUtil::isExclude($fieldConfig)) {
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }
}
