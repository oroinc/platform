<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SaveRelations implements ProcessorInterface
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
            return;
        }

        $associationName = $context->getAssociationName();
        if (!$context->getParentConfig()->hasField($associationName)) {
            return;
        }

        $associationFieldConfig = $context->getParentConfig()->getField($context->getAssociationName());

        if (!$associationFieldConfig->has('association-field')) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($context->getClassName());

        $associationKind = $associationFieldConfig->get('association-kind');
        $setterMethodName = AssociationNameGenerator::generateSetTargetMethodName($associationKind) ;

        $form = $context->getForm();
        $associationForm = $form->get($associationName);
        foreach ($associationForm->getNormData() as $item) {
            $this->propertyAccessor->setValue($item, $setterMethodName, $context->getParentEntity());
            $em->persist($item);
        }

        $em->flush();
    }
}
