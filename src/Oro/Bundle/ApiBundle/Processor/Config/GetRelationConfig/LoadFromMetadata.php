<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads default configuration based on entity metadata.
 */
class LoadFromMetadata implements ProcessorInterface
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
        /** @var RelationConfigContext $context */

        $definition = $context->getResult();
        if ($definition->hasFields()) {
            // already initialized
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $definition->setExcludeAll();
        $definition->setCollapsed();
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        // add all identifier fields
        $targetIdFields = $metadata->getIdentifierFieldNames();
        foreach ($targetIdFields as $fieldName) {
            $definition->addField($fieldName);
        }
        // add "__class__" meta property if an entity uses Doctrine table inheritance
        if ($metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $classNameField = $definition->addField(ConfigUtil::CLASS_NAME);
            $classNameField->setMetaProperty(true);
            $classNameField->setDataType(DataType::STRING);
        }
    }
}
