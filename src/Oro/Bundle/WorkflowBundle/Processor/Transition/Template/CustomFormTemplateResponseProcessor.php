<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Template;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

class CustomFormTemplateResponseProcessor implements ProcessorInterface
{
    const DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE = 'OroWorkflowBundle:Widget:widget/transitionCustomForm.html.twig';

    /** @var \Twig_Environment */
    private $twig;

    /** @var FormTemplateDataProviderRegistry */
    private $templateDataProviderRegistry;

    /**
     * @param \Twig_Environment $twig
     * @param FormTemplateDataProviderRegistry $templateDataProviderRegistry
     */
    public function __construct(\Twig_Environment $twig, FormTemplateDataProviderRegistry $templateDataProviderRegistry)
    {
        $this->twig = $twig;
        $this->templateDataProviderRegistry = $templateDataProviderRegistry;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$this->isApplicable($context)) {
            return;
        }

        $templateData = array_merge($this->getTemplateData($context), $context->get('template_parameters'));

        $response = new Response();
        $response->setContent($this->twig->render($this->getTemplate($context), $templateData));

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

        return $transition->getDialogTemplate() ?: self::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE;
    }

    /**
     * @param TransitionContext $context
     * @return array
     */
    private function getTemplateData(TransitionContext $context)
    {
        $transition = $context->getTransition();
        $workflowItem = $context->getWorkflowItem();

        $dataProvider = $this->templateDataProviderRegistry->get($transition->getFormDataProvider());

        return $dataProvider->getData(
            $workflowItem->getData()->get($transition->getFormDataAttribute()),
            $context->getForm(),
            $context->getRequest()
        );
    }

    /**
     * @param TransitionContext $context
     * @return bool
     */
    public function isApplicable(TransitionContext $context): bool
    {
        if ($context->isSaved()) {
            return false;
        }

        if (!$context->getResultType() instanceof TemplateResultType) {
            return false;
        }

        if (!$context->isCustomForm()) {
            return false;
        }

        return true;
    }
}
