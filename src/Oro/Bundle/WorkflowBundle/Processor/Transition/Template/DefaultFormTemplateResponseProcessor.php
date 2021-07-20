<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Template;

use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Creates a response that contains the transition form.
 */
class DefaultFormTemplateResponseProcessor implements ProcessorInterface
{
    const DEFAULT_TRANSITION_TEMPLATE = '@OroWorkflow/Widget/widget/transitionForm.html.twig';

    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if ($context->isSaved() || !$context->getResultType() instanceof TemplateResultType) {
            return;
        }

        $response = new Response();
        $response->setContent(
            $this->twig->render($this->getTemplate($context), $context->get('template_parameters'))
        );

        $context->setResult($response);
        $context->setProcessed(true);
    }

    /**
     * @param TransitionContext $context
     * @return string
     */
    private function getTemplate(TransitionContext $context)
    {
        $transition = $context->getTransition();

        return $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE;
    }
}
