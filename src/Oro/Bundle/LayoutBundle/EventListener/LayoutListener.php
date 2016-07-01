<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;

use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

/**
 * The LayoutListener class handles the @Layout annotation.
 */
class LayoutListener
{
    /**
     * @var LayoutHelper
     */
    protected $layoutHelper;

    /**
     * @var LayoutManager
     */
    protected $layoutManager;

    /**
     * @var LayoutDataCollector
     */
    protected $layoutDataCollector;

    /**
     * @param LayoutHelper $layoutHelper
     * @param LayoutManager $layoutManager
     * @param LayoutDataCollector $layoutDataCollector
     */
    public function __construct(
        LayoutHelper $layoutHelper,
        LayoutManager $layoutManager,
        LayoutDataCollector $layoutDataCollector
    ) {
        $this->layoutHelper = $layoutHelper;
        $this->layoutManager = $layoutManager;
        $this->layoutDataCollector = $layoutDataCollector;
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

        $parameters = $event->getControllerResult();
        if (is_array($parameters)) {
            $context = new LayoutContext();
            foreach ($parameters as $key => $value) {
                $context->set($key, $value);
            }
            $this->configureContext($context, $layoutAnnotation);
            $this->layoutDataCollector->collectContextItems($context);
            $layout = $this->getLayout($context, $layoutAnnotation);
        } elseif ($parameters instanceof ContextInterface) {
            $this->configureContext($parameters, $layoutAnnotation);
            $this->layoutDataCollector->collectContextItems($parameters);
            $layout = $this->getLayout($parameters, $layoutAnnotation);
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
        
        $this->layoutDataCollector->collectViews($layout->getView());

        $response = new Response();
        $response->setContent($layout->render());
        $event->setResponse($response);
    }

    /**
     * Get the layout and add parameters to the layout context.
     *
     * @param ContextInterface $context
     * @param LayoutAnnotation $layoutAnnotation
     *
     * @return Layout
     */
    protected function getLayout(ContextInterface $context, LayoutAnnotation $layoutAnnotation)
    {
        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        $blockThemes = $layoutAnnotation->getBlockThemes();
        if (!empty($blockThemes)) {
            $layoutBuilder->setBlockTheme($blockThemes);
        }

        return $layoutBuilder->getLayout($context);
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

        $vars = $layoutAnnotation->getVars();
        if (!empty($vars)) {
            $context->getResolver()->setRequired($vars);
        }
    }
}
