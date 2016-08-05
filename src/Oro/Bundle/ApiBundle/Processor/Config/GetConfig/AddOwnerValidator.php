<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

/**
 * For "create" action adds NotNull validation constraint for "owner" field.
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

        $this->addValidators($context->getResult(), $entityClass, $context->getTargetAction());
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     */
    protected function addValidators(EntityDefinitionConfig $definition, $entityClass, $targetAction)
    {
        $fieldName = $this->ownershipMetadataProvider->getMetadata($entityClass)->getOwnerFieldName();
        if (!$fieldName) {
            return;
        }
        $field = $definition->findField($fieldName, true);
        if (null === $field) {
            return;
        }

        // add NotNull validation
        if (ApiActions::CREATE === $targetAction) {
            $field->addFormConstraint(new NotNull());
        }

        // add NotBlank constraint
        $property = $field->getPropertyPath() ?: $fieldName;
        if (!$this->validationHelper->hasValidationConstraintForProperty($entityClass, $property, NotBlank::class)) {
            $field->addFormConstraint(new NotBlank());
        }

        // add owner validator
        if (!$this->validationHelper->hasValidationConstraintForClass($entityClass, Owner::class)) {
            $definition->addFormConstraint(new Owner());
        }
    }
}
