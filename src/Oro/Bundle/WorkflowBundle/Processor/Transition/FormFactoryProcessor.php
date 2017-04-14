<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormFactoryInterface;

class FormFactoryProcessor implements ProcessorInterface
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $formData = $context->getFormData();
        if (!$formData) {
            throw new \UnexpectedValueException('Data for transition form is not defined');
        }

        $context->setForm(
            $this->formFactory->create(
                $context->getTransition()->getFormType(),
                $formData,
                $context->getFormOptions()
            )
        );
    }
}
