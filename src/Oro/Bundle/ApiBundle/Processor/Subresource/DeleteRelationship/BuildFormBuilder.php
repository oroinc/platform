<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Form\DataMapper\RemoveRelationshipMapper;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildFormBuilder as BaseBuildFormBuilder;

/**
 * Builds the form builder based on the parent entity metadata and configuration
 * and sets it to the Context.
 */
class BuildFormBuilder extends BaseBuildFormBuilder
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /**
     * @param FormFactoryInterface      $formFactory
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(FormFactoryInterface $formFactory, PropertyAccessorInterface $propertyAccessor)
    {
        parent::__construct($formFactory);
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormBuilder(ChangeRelationshipContext $context)
    {
        $formBuilder = parent::getFormBuilder($context);
        $formBuilder->setDataMapper(new RemoveRelationshipMapper($this->propertyAccessor));

        return $formBuilder;
    }
}
