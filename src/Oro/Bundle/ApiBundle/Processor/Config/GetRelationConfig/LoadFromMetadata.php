<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
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
        $targetIdFields = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($context->getClassName());
        foreach ($targetIdFields as $fieldName) {
            $definition->addField($fieldName);
        }
    }
}
