<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Updates configuration to ask the EntitySerializer that the entity class should be returned
 * together with related entity data in case if the entity implemented using Doctrine table inheritance.
 */
class SetTypeForTableInheritanceRelations implements ProcessorInterface
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

        $definition = $context->getResult();
        if (!isset($definition[ConfigUtil::FIELDS])
            || !is_array($definition[ConfigUtil::FIELDS])
            || !ConfigUtil::isExcludeAll($definition)
        ) {
            // expected normalized configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        if ($this->updateRelations($definition, $entityClass)) {
            $context->setResult($definition);
        }
    }

    /**
     * @param array  $definition
     * @param string $entityClass
     *
     * @return bool
     */
    protected function updateRelations(array &$definition, $entityClass)
    {
        $hasChanges = false;

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        foreach ($definition[ConfigUtil::FIELDS] as $fieldName => &$fieldConfig) {
            if (!is_array($fieldConfig) || empty($fieldConfig[ConfigUtil::DEFINITION][ConfigUtil::FIELDS])) {
                continue;
            }

            $fieldDefinition = $fieldConfig[ConfigUtil::DEFINITION];

            $propertyPath = ConfigUtil::getPropertyPath($fieldDefinition, $fieldName);
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $mapping        = $metadata->getAssociationMapping($propertyPath);
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($mapping['targetEntity']);
            if ($targetMetadata->inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
                continue;
            }

            if (!is_array($fieldDefinition[ConfigUtil::FIELDS])) {
                $fieldDefinition[ConfigUtil::FIELDS] = [
                    $fieldDefinition[ConfigUtil::FIELDS] => null
                ];
            }

            $fieldDefinition[ConfigUtil::FIELDS][ConfigUtil::CLASS_NAME] = null;

            $fieldConfig[ConfigUtil::DEFINITION] = $fieldDefinition;

            $hasChanges = true;
        }

        return $hasChanges;
    }
}
