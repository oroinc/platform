<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Creates form instances for workflow transitions.
 *
 * This processor uses the Symfony form factory to create form instances based on the transition's
 * form type configuration. It requires that form data and form options have been set in the context
 * by previous processors. The created form is stored in the context for subsequent processing,
 * such as rendering or handling form submissions.
 */
class FormFactoryProcessor implements ProcessorInterface
{
    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
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
