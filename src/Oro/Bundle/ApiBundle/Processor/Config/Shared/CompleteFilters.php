<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class CompleteFilters implements ProcessorInterface
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

        $filters = $context->getFilters();
        if (empty($filters)) {
            // nothing to normalize
            return;
        }

        $fields = ConfigUtil::getArrayValue($filters, ConfigUtil::FIELDS);

        if (ConfigUtil::isExcludeAll($filters)) {
            $fields = $this->removeExclusions($fields);
        } else {
            $entityClass = $context->getClassName();
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $fields = $this->removeExclusions(
                    $this->completeFilters($fields, $entityClass, $context->getResult())
                );
            }
        }

        $context->setFilters(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                ConfigUtil::FIELDS           => $fields
            ]
        );
    }

    /**
     * @param array      $filters
     * @param string     $entityClass
     * @param array|null $config
     *
     * @return array
     */
    protected function completeFilters(array $filters, $entityClass, $config)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $filters = $this->getFieldFilters($filters, $metadata);
        $filters = $this->getAssociationFilters($filters, $metadata);

        if (!empty($config)) {
            foreach ($filters as $fieldName => &$fieldConfig) {
                if ($this->isExcludedField($config, $fieldName)) {
                    $fieldConfig[ConfigUtil::EXCLUDE] = true;
                }
            }
        }

        return $filters;
    }

    /**
     * @param array         $filters
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getFieldFilters(array $filters, ClassMetadata $metadata)
    {
        $indexedFields = $this->doctrineHelper->getIndexedFields($metadata, false);
        foreach ($indexedFields as $fieldName => $type) {
            if (array_key_exists($fieldName, $filters)) {
                // already defined
                continue;
            }
            $filters[$fieldName] = [
                ConfigUtil::DATA_TYPE => $type
            ];
        }

        return $filters;
    }

    /**
     * @param array         $filters
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getAssociationFilters(array $filters, ClassMetadata $metadata)
    {
        $relations = $this->doctrineHelper->getIndexedRelations($metadata);
        foreach ($relations as $fieldName => $dataType) {
            if (array_key_exists($fieldName, $filters)) {
                // already defined
                continue;
            }
            $filters[$fieldName] = [
                ConfigUtil::DATA_TYPE   => $dataType,
                ConfigUtil::ALLOW_ARRAY => true
            ];

        }

        return $filters;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    protected function removeExclusions(array $filters)
    {
        return array_filter(
            $filters,
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
