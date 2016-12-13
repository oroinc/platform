<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Form\EventListener\CreateListener;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildFormBuilder as BaseBuildFormBuilder;

/**
 * Builds the form builder based on the entity metadata and configuration
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
    protected function getFormBuilder(FormContext $context)
    {
        $formBuilder = parent::getFormBuilder($context);
        $formBuilder->addEventSubscriber(new CreateListener());

        return $formBuilder;
    }
}
