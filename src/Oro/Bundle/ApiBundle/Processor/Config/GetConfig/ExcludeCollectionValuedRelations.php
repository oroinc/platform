<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Excludes "to-many" relations.
 */
class ExcludeCollectionValuedRelations implements ProcessorInterface
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

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName());
        $definition = $context->getResult();
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                // already excluded
                continue;
            }

            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if ($metadata->hasAssociation($propertyPath)
                // @todo: temporary exclude all associations. see BAP-10008
                // && $metadata->isCollectionValuedAssociation($propertyPath)
            ) {
                $field->setExcluded();
            }
        }
    }
}
