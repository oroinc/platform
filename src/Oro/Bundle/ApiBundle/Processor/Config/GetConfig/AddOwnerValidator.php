<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;

/**
 * Adds NotBlank validation constraint for "owner" field.
 * Adds Owner validation constraint for the entity.
 */
class AddOwnerValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var ValidationHelper */
    protected $validationHelper;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param ValidationHelper          $validationHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        ValidationHelper $validationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->validationHelper = $validationHelper;
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

        // add NotBlank constraint
        if (!$this->validationHelper->hasValidationConstraintForProperty(
            $entityClass,
            $field->getPropertyPath() ?: $fieldName,
            'Symfony\Component\Validator\Constraints\NotBlank'
        )) {
            $fieldOptions = $field->getFormOptions();
            $fieldOptions['constraints'][] = new NotBlank();
            $field->setFormOptions($fieldOptions);
        }

        // add owner validator
        if (!$this->validationHelper->hasValidationConstraintForClass(
            $entityClass,
            'Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner'
        )) {
            $entityOptions = $definition->getFormOptions();
            $entityOptions['constraints'][] = new Owner();
            $definition->setFormOptions($entityOptions);
        }
    }
}
