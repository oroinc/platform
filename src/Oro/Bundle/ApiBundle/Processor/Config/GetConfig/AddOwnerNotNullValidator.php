<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds NotNull validation constraint for "owner" field.
 */
class AddOwnerNotNullValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, OwnershipMetadataProvider $ownershipMetadataProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
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

        $definition = $context->getResult();
        $fieldName = $this->ownershipMetadataProvider->getMetadata($entityClass)->getOwnerFieldName();
        if (!$fieldName) {
            return;
        }
        $field = $definition->findField($fieldName, true);
        if (null === $field) {
            return;
        }

        $fieldOptions = $field->getFormOptions();
        $fieldOptions['constraints'][] = new NotNull();
        $field->setFormOptions($fieldOptions);
    }
}
