<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Validator\Constraints as Assert;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds AccessGranted validation constraint for all ORM associations.
 * Adds HasAdderAndRemover validation constraint for all "to-many" associations.
 */
class AddAssociationValidators implements ProcessorInterface
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
    protected function addValidatorsForEntityAssociations(EntityDefinitionConfig $definition, $entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $fieldName = $field->getPropertyPath() ?: $fieldName;
            if ($metadata->hasAssociation($fieldName)) {
                $fieldOptions = $field->getFormOptions();
                if ($metadata->isCollectionValuedAssociation($fieldName)) {
                    $fieldOptions['constraints'][] = new Assert\HasAdderAndRemover(
                        ['class' => $entityClass, 'property' => $fieldName]
                    );
                    $fieldOptions['constraints'][] = new Assert\All(new Assert\AccessGranted());
                } else {
                    $fieldOptions['constraints'][] = new Assert\AccessGranted();
                }
                $field->setFormOptions($fieldOptions);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function addValidatorsForObjectAssociations(EntityDefinitionConfig $definition, $entityClass)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getTargetClass() && $field->isCollectionValuedTarget()) {
                $fieldOptions = $field->getFormOptions();
                $fieldOptions['constraints'][] = new Assert\HasAdderAndRemover(
                    ['class' => $entityClass, 'property' => $field->getPropertyPath() ?: $fieldName]
                );
                $field->setFormOptions($fieldOptions);
            }
        }
    }
}
