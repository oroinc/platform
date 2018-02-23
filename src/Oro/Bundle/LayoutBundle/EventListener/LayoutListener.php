<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * The LayoutListener class handles the @Layout annotation.
 */
class LayoutListener
{
    /** @var LayoutHelper */
    private $layoutHelper;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param LayoutHelper       $layoutHelper
     * @param ContainerInterface $container
     */
    public function __construct(LayoutHelper $layoutHelper, ContainerInterface $container)
    {
        $this->layoutHelper = $layoutHelper;
        $this->container = $container;
    }

    /**
     * Renders the layout and initializes the content of a new response object
     * with the rendered layout.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws LogicException if @Layout annotation is used in incorrect way
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        $layoutAnnotation = $this->layoutHelper->getLayoutAnnotation($request);
        if (!$layoutAnnotation) {
            return;
        }
        if ($request->attributes->get('_template')) {
            throw new LogicException(
                'The @Template() annotation cannot be used together with the @Layout() annotation.'
            );
        }

        $layout = null;
        $context = null;
        $parameters = $event->getControllerResult();
        if (is_array($parameters)) {
            $context = new LayoutContext($parameters, (array) $layoutAnnotation->getVars());
        } elseif ($parameters instanceof ContextInterface) {
            $context = $parameters;
            $vars = $layoutAnnotation->getVars();
            if (!empty($vars)) {
                $context->getResolver()->setRequired($vars);
            }
        } elseif ($parameters instanceof Layout) {
            if (!$layoutAnnotation->isEmpty()) {
                throw new LogicException(
                    'The empty @Layout() annotation must be used when '
                    . 'the controller returns an instance of "Oro\Component\Layout\Layout".'
                );
            }
            $layout = $parameters;
        } else {
            return;
        }

        if ($layout) {
            $response = new Response($layout->render());
        } else {
            $this->configureContext($context, $layoutAnnotation);
            /** @var LayoutManager $layoutManager */
            $layoutManager = $this->container->get('oro_layout.layout_manager');
            $layoutManager->getLayoutBuilder()->setBlockTheme($layoutAnnotation->getBlockThemes());
            $response = $this->getLayoutResponse($context, $request, $layoutManager);
        }

        $event->setResponse($response);
    }

    /**
     * Configures the layout context.
     *
     * @param ContextInterface $context
     * @param LayoutAnnotation $layoutAnnotation
     */
    protected function configureContext(ContextInterface $context, LayoutAnnotation $layoutAnnotation)
    {
        $action = $layoutAnnotation->getAction();
        if (!empty($action)) {
            $currentAction = $context->getOr('action');
            if (empty($currentAction)) {
                $context->set('action', $action);
            }
        }

        $theme = $layoutAnnotation->getTheme();
        if (!empty($theme)) {
            $currentTheme = $context->getOr('theme');
            if (empty($currentTheme)) {
                $context->set('theme', $theme);
            }
        }
    }

    /**
     * @param ContextInterface $context
     * @param Request          $request
     * @param LayoutManager    $layoutManager
     *
     * @return Response
     */
    protected function getLayoutResponse(
        ContextInterface $context,
        Request $request,
        LayoutManager $layoutManager
    ) {
        $blockIds = $request->get('layout_block_ids');
        if (is_array($blockIds) && $blockIds) {
            $response = [];
            foreach ($blockIds as $blockId) {
                if ($blockId) {
                    $layout = $layoutManager->getLayout($context, $blockId);
                    $response[$blockId] = $layout->render();
                }
            }
            $response = new JsonResponse($response);
        } else {
            $layout = $layoutManager->getLayout($context);
            $response = new Response($layout->render());
        }
        return $response;
    }
}
