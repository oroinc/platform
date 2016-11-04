<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Saves inverse extend association part ORM entities to database.
 */
class SaveInverseRelations implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessorInterface $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var AddRelationshipContext $context */

        if (!$context->hasForm()) {
            // context has no form - nothing to save
            return;
        }

        $associationName = $context->getAssociationName();
        if (!$context->getParentConfig()->hasField($associationName)) {
            // parent entity config has no association field config
            return;
        }

        $associationFieldConfig = $context->getParentConfig()->getField($context->getAssociationName());
        if (!$associationFieldConfig->has('association-field')) {
            // association field config is no inverse part of extended association
            return;
        }

        $form = $context->getForm();
        if (!$form->has($associationName)) {
            // form has no inverse association field
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($context->getClassName());
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        $associationForm = $form->get($associationName);
        $associationKind = $associationFieldConfig->get('association-kind');
        $setterMethodName = AssociationNameGenerator::generateSetTargetMethodName($associationKind) ;
        foreach ($associationForm->getNormData() as $item) {
            // set the association target (context parent entity) to the association source object
            $this->propertyAccessor->setValue($item, $setterMethodName, $context->getParentEntity());
            // save the association source object
            $em->persist($item);
        }

        $em->flush();
    }
}
