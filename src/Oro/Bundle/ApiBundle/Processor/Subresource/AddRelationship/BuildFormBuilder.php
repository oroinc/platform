<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship;

use Oro\Bundle\ApiBundle\Form\DataMapper\AppendRelationshipMapper;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildFormBuilder as BaseBuildFormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Builds the form builder based on the parent entity metadata and configuration
 * and sets it to the context.
 */
class BuildFormBuilder extends BaseBuildFormBuilder
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(FormHelper $formHelper, PropertyAccessorInterface $propertyAccessor)
    {
        parent::__construct($formHelper);
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormBuilder(ChangeRelationshipContext $context): FormBuilderInterface
    {
        $formBuilder = parent::getFormBuilder($context);
        $formBuilder->setDataMapper(
            new AppendRelationshipMapper($this->propertyAccessor, $context->getEntityMapper())
        );

        return $formBuilder;
    }
}
