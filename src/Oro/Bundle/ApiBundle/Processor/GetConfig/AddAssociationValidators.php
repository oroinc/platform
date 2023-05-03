<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Validator\Constraints as Assert;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds AccessGranted validation constraint for all ORM associations.
 * Adds HasAdderAndRemover validation constraint for all "to-many" associations.
 */
class AddAssociationValidators implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AssociationAccessExclusionProviderRegistry $associationAccessExclusionProviderRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AssociationAccessExclusionProviderRegistry $associationAccessExclusionProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationAccessExclusionProviderRegistry = $associationAccessExclusionProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->addValidatorsForEntityAssociations($definition, $entityClass, $context->getRequestType());
        } else {
            $this->addValidatorsForObjectAssociations($definition, $entityClass);
        }
    }

    private function addValidatorsForEntityAssociations(
        EntityDefinitionConfig $definition,
        string $entityClass,
        RequestType $requestType
    ): void {
        $associationAccessExclusionProvider = $this->associationAccessExclusionProviderRegistry
            ->getAssociationAccessExclusionProvider($requestType);
        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $fieldName = $field->getPropertyPath($fieldName);
            if (ConfigUtil::IGNORE_PROPERTY_PATH !== $fieldName
                && $metadata->hasAssociation($fieldName)
                && !$associationAccessExclusionProvider->isIgnoreAssociationAccessCheck($entityClass, $fieldName)
            ) {
                $accessGrantedConstraint = new Assert\AccessGranted(['groups' => ['api']]);
                if ($metadata->isCollectionValuedAssociation($fieldName)) {
                    if (!$this->isByReference($field)) {
                        $field->addFormConstraint(new Assert\HasAdderAndRemover([
                            'class'    => $entityClass,
                            'property' => $fieldName,
                            'groups'   => ['api']
                        ]));
                    }
                    $field->addFormConstraint(new Assert\All($accessGrantedConstraint));
                } else {
                    $field->addFormConstraint($accessGrantedConstraint);
                }
            }
        }
    }

    private function addValidatorsForObjectAssociations(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getTargetClass() && $field->isCollectionValuedAssociation()) {
                $fieldName = $field->getPropertyPath($fieldName);
                // to avoid duplication, check if the constraint already exist
                // e.g. the constraint can be already added if a model for API resource inherited from ORM entity
                if (ConfigUtil::IGNORE_PROPERTY_PATH !== $fieldName
                    && !$this->isByReference($field)
                    && !$this->isHasAdderAndRemoverConstraintExist($field, $entityClass, $fieldName)
                ) {
                    $field->addFormConstraint(new Assert\HasAdderAndRemover([
                        'class'    => $entityClass,
                        'property' => $fieldName,
                        'groups'   => ['api']
                    ]));
                }
            }
        }
    }

    private function isByReference(EntityDefinitionFieldConfig $field): bool
    {
        $formOptions = $field->getFormOptions();

        return $formOptions && isset($formOptions['by_reference']) && $formOptions['by_reference'];
    }

    private function isHasAdderAndRemoverConstraintExist(
        EntityDefinitionFieldConfig $field,
        string $entityClass,
        string $fieldName
    ): bool {
        $hasConstraint = false;
        $constraints = $field->getFormConstraints();
        if (!empty($constraints)) {
            foreach ($constraints as $constraint) {
                if ($constraint instanceof Assert\HasAdderAndRemover
                    && $constraint->property === $fieldName
                    && is_a($entityClass, $constraint->class, true)
                ) {
                    $hasConstraint = true;
                    break;
                }
            }
        }

        return $hasConstraint;
    }
}
