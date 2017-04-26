<?php

namespace Oro\Bundle\WorkflowBundle\Handler\Helper;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;

use Symfony\Component\HttpFoundation\Response;

use Twig_Environment;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class TransitionHelper
{
    /** @var ViewHandlerInterface */
    protected $viewHandler;

    /** @var Twig_Environment */
    protected $twig;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param Twig_Environment $twig
     */
    public function __construct(ViewHandlerInterface $viewHandler, Twig_Environment $twig)
    {
        $this->viewHandler = $viewHandler;
        $this->twig = $twig;
    }

    /**
     * @param WorkflowItem|null $workflowItem
     * @param int $responseCode
     * @param string|null $responseMessage
     *
     * @return Response
     */
    public function createCompleteResponse(
        WorkflowItem $workflowItem = null,
        $responseCode = null,
        $responseMessage = null
    ) {
        $transitResponseContent = null;
        if (!$responseCode) {
            $view = View::create([
                'workflowItem' => $workflowItem,
            ]);
            $view->setFormat('json');
            $transitResponse = $this->viewHandler->handle($view);
            $responseCode = $transitResponse->getStatusCode();
            $transitResponseContent = json_decode($transitResponse->getContent());
        }

        $content = $this->twig->render(
            'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
            [
                'response'          => $transitResponseContent,
                'responseCode'      => $responseCode,
                'responseMessage'   => $responseMessage,
                'transitionSuccess' => $responseCode === 200,
            ]
        );

        return new Response($content);
    }
}
