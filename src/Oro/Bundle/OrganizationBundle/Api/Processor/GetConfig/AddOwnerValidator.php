<?php

namespace Oro\Bundle\OrganizationBundle\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the validation constraint that is used to validate that
 * an owner of an entity can be changed.
 */
class AddOwnerValidator implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private OwnershipMetadataProviderInterface $ownershipMetadataProvider;
    private ValidationHelper $validationHelper;
    private string $ownerConstraintClass;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        ValidationHelper $validationHelper,
        string $ownerConstraintClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->validationHelper = $validationHelper;
        $this->ownerConstraintClass = $ownerConstraintClass;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->addValidators($context->getResult(), $entityClass);
    }

    private function addValidators(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $fieldName = $this->ownershipMetadataProvider->getMetadata($entityClass)->getOwnerFieldName();
        if (!$fieldName) {
            return;
        }
        $field = $definition->findField($fieldName, true);
        if (null === $field || $field->isExcluded()) {
            return;
        }

        // add owner validator
        $ownerConstraintClass = $this->ownerConstraintClass;
        if (!$this->validationHelper->hasValidationConstraintForClass($entityClass, $ownerConstraintClass)) {
            $definition->addFormConstraint(new $ownerConstraintClass(['groups' => ['api']]));
        }
    }
}
