<?php

namespace Oro\Bundle\OrganizationBundle\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the validation constraint that is used to validate that
 * an owner of the entity can be changed.
 */
class AddOwnerValidator implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var ValidationHelper */
    protected $validationHelper;

    /**
     * @param DoctrineHelper                     $doctrineHelper
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param ValidationHelper                   $validationHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
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

        $this->addValidators($context->getResult(), $entityClass);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function addValidators(EntityDefinitionConfig $definition, $entityClass)
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
        $ownerConstraintClass = $this->getOwnerConstraintClass();
        if (!$this->validationHelper->hasValidationConstraintForClass($entityClass, $ownerConstraintClass)) {
            $definition->addFormConstraint(new $ownerConstraintClass(['groups' => ['api']]));
        }
    }

    /**
     * @return string
     */
    protected function getOwnerConstraintClass()
    {
        return Owner::class;
    }
}
