<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
    /** @var DoctrineHelper */
    private $doctrineHelper;

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
        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->addValidatorsForEntityAssociations($definition, $entityClass);
        } else {
            $this->addValidatorsForObjectAssociations($definition, $entityClass);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function addValidatorsForEntityAssociations(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $fieldName = $field->getPropertyPath($fieldName);
            if ($metadata->hasAssociation($fieldName)) {
                if ($metadata->isCollectionValuedAssociation($fieldName)) {
                    $field->addFormConstraint(new Assert\HasAdderAndRemover([
                        'class'    => $entityClass,
                        'property' => $fieldName,
                        'groups'   => ['api']
                    ]));
                    $field->addFormConstraint(new Assert\All(new Assert\AccessGranted(['groups' => ['api']])));
                } else {
                    $field->addFormConstraint(new Assert\AccessGranted(['groups' => ['api']]));
                }
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function addValidatorsForObjectAssociations(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getTargetClass() && $field->isCollectionValuedAssociation()) {
                $fieldName = $field->getPropertyPath($fieldName);
                // to avoid duplication, check if the constraint already exist
                // e.g. the constraint can be already added if a model for API resource inherited from ORM entity
                if (!$this->isHasAdderAndRemoverConstraintExist($field, $entityClass, $fieldName)) {
                    $field->addFormConstraint(new Assert\HasAdderAndRemover([
                        'class'    => $entityClass,
                        'property' => $fieldName,
                        'groups'   => ['api']
                    ]));
                }
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $entityClass
     * @param string                      $fieldName
     *
     * @return bool
     */
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
                    && \is_a($entityClass, $constraint->class, true)
                ) {
                    $hasConstraint = true;
                    break;
                }
            }
        }

        return $hasConstraint;
    }
}
