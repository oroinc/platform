<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetMaxRelatedEntities implements ProcessorInterface
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

        $maxRelatedEntities = $context->getMaxRelatedEntities();
        if (null === $maxRelatedEntities || $maxRelatedEntities < 0) {
            // there is no limit to the number of related entities
            return;
        }

        $definition = $context->getResult();
        if (empty($definition)) {
            // nothing to update
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->setLimits($definition, $entityClass, $maxRelatedEntities);
        $context->setResult($definition);
    }

    /**
     * @param array  $definition
     * @param string $entityClass
     * @param int    $limit
     */
    protected function setLimits(array &$definition, $entityClass, $limit)
    {
        if (isset($definition[ConfigUtil::FIELDS]) && is_array($definition[ConfigUtil::FIELDS])) {
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
            foreach ($definition[ConfigUtil::FIELDS] as $fieldName => &$fieldConfig) {
                if (is_array($fieldConfig)) {
                    $propertyPath = ConfigUtil::getPropertyPath($fieldConfig, $fieldName);
                    $path         = ConfigUtil::explodePropertyPath($propertyPath);
                    if (count($path) === 1) {
                        $this->setFieldLimit($fieldConfig, $metadata, $propertyPath, $limit);
                    } else {
                        $linkedField    = array_pop($path);
                        $linkedMetadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);
                        if (null !== $linkedMetadata) {
                            $this->setFieldLimit($fieldConfig, $linkedMetadata, $linkedField, $limit);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array         $fieldConfig
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param int           $limit
     */
    protected function setFieldLimit(array &$fieldConfig, ClassMetadata $metadata, $fieldName, $limit)
    {
        if ($metadata->hasAssociation($fieldName)) {
            if (!array_key_exists(ConfigUtil::MAX_RESULTS, $fieldConfig)
                && $metadata->isCollectionValuedAssociation($fieldName)
            ) {
                $fieldConfig[ConfigUtil::MAX_RESULTS] = $limit;
            }
            $this->setLimits($fieldConfig, $metadata->name, $limit);
        }
    }
}
