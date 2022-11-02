<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Template;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\WorkflowItemSerializerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Creates a response that contains transition completion data (success or failure).
 */
class FormSubmitTemplateResponseProcessor implements ProcessorInterface
{
    private const WIDGET_TEMPLATE_TRANSITION_COMPLETE = '@OroWorkflow/Widget/widget/transitionComplete.html.twig';

    private WorkflowItemSerializerInterface $workflowItemSerializer;
    private ViewHandlerInterface $viewHandler;
    private Environment $twig;

    public function __construct(
        WorkflowItemSerializerInterface $workflowItemSerializer,
        ViewHandlerInterface $viewHandler,
        Environment $twig
    ) {
        $this->workflowItemSerializer = $workflowItemSerializer;
        $this->viewHandler = $viewHandler;
        $this->twig = $twig;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var TransitionContext $context */

        if (!$context->getResultType() instanceof TemplateResultType) {
            return;
        }

        $context->setResult($this->createCompleteResponse($context));
        $context->setProcessed(true);
    }

    private function createCompleteResponse(TransitionContext $context): Response
    {
        $responseCode = $context->get('responseCode');
        $responseMessage = $context->get('responseMessage');

        $transitResponseContent = null;
        if (!$responseCode) {
            $view = View::create([
                'workflowItem' => $this->workflowItemSerializer->serialize($context->getWorkflowItem())
            ]);
            $view->setFormat('json');
            $view->getContext()->setSerializeNull(true);
            $transitResponse = $this->viewHandler->handle($view);
            $responseCode = $transitResponse->getStatusCode();
            $transitResponseContent = json_decode($transitResponse->getContent(), false, 512, JSON_THROW_ON_ERROR);
        }

        $content = $this->twig->render(
            self::WIDGET_TEMPLATE_TRANSITION_COMPLETE,
            [
                'response' => $transitResponseContent,
                'responseCode' => $responseCode,
                'responseMessage' => $responseMessage,
                'transitionSuccess' => $responseCode === 200,
            ]
        );

        return new Response($content);
    }
}
