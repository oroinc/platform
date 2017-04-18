<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Template;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

class FormSubmitTemplateResponseProcessor implements ProcessorInterface
{
    const WIDGET_TEMPLATE_TRANSITION_COMPLETE = 'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig';

    /** @var ViewHandlerInterface */
    private $viewHandler;

    /** @var \Twig_Environment */
    private $twig;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param \Twig_Environment $twig
     */
    public function __construct(ViewHandlerInterface $viewHandler, \Twig_Environment $twig)
    {
        $this->viewHandler = $viewHandler;
        $this->twig = $twig;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$context->getResultType() instanceof TemplateResultType) {
            return;
        }

        $context->setResult($this->createCompleteResponse($context));
        $context->setProcessed(true);
    }

    /**
     * @param TransitionContext $context
     *
     * @return Response
     */
    private function createCompleteResponse(TransitionContext $context)
    {
        $responseCode = $context->get('responseCode');
        $responseMessage = $context->get('responseMessage');

        $transitResponseContent = null;
        if (!$responseCode) {
            $view = View::create([
                'workflowItem' => $context->getWorkflowItem(),
            ]);
            $view->setFormat('json');
            $transitResponse = $this->viewHandler->handle($view);
            $responseCode = $transitResponse->getStatusCode();
            $transitResponseContent = json_decode($transitResponse->getContent());
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
