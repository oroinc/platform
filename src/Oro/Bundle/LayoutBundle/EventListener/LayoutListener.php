<?php

namespace Oro\Bundle\LayoutBundle\EventListener;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;


/**
 * The LayoutListener class handles the @Layout annotation.
 */
class LayoutListener implements EventSubscriberInterface
{
    /**
     * @var LayoutManager
     */
    protected $layoutManager;

    /**
     * Constructor.
     *
     * @param LayoutManager $layoutManager
     */
    public function __construct(LayoutManager $layoutManager)
    {
        $this->layoutManager = $layoutManager;
    }

    /**
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     *
     * @throws \InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->has('_' . LayoutAnnotation::ALIAS)) {
            return;
        }

        $configuration = $request->attributes->get('_' . LayoutAnnotation::ALIAS);
        // If the action is not explicitly defined we'll try to get it from controller action method
        if (!$configuration->getAction()) {
            if (!preg_match('/^(.+)Action$/', $controller[1], $matchAction)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The "%s" method does not look like an action method (it does not end with Action)',
                        $controller[1]
                    )
                );
            }
            $configuration->setAction($matchAction[1]);
        }
    }

    /**
     * Renders the layout and initializes a new response object with the
     * rendered layout content.
     *
     * @param GetResponseForControllerResultEvent $event A GetResponseForControllerResultEvent instance
     *
     * @throws LogicException
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $result  = $event->getControllerResult();

        /** @var LayoutAnnotation $layoutAnnotation */
        if (!$layoutAnnotation = $request->attributes->get('_' . LayoutAnnotation::ALIAS)) {
            return;
        }

        $response = new Response();
        if (is_array($result)) {
            $layoutContext = $this->prepareContext($result, $layoutAnnotation);
            $layout        = $this->getLayout($layoutContext, $layoutAnnotation);
        } elseif ($result instanceof ContextInterface) {
            $layoutContext = $this->prepareContext([], $layoutAnnotation, $result);
            $layout        = $this->getLayout($layoutContext, $layoutAnnotation);
        } elseif ($result instanceof Layout) {
            if ($layoutAnnotation->getTheme()
                || $layoutAnnotation->getAction()
                || $layoutAnnotation->getVars()
                || $layoutAnnotation->getTemplates()
            ) {
                throw new LogicException('@Layout annotation configured improperly. Should use empty @Layout()'
                    . ' configuration when returning an instance of Oro\\Component\\Layout\\Layout in the response.');
            }
            $layout        = $result;
        } else {
            return;
        }

        $response->setContent($layout->render());
        $event->setResponse($response);
    }

    /**
     * Get the layout and add parameters to the layout context
     *
     * @param ContextInterface $layoutContext
     * @param LayoutAnnotation $layoutAnnotation
     * @return Layout
     */
    protected function getLayout($layoutContext, $layoutAnnotation)
    {
        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        $layoutBuilder->add('root', null, 'root');

        if ($templates = $layoutAnnotation->getTemplates()) {
            $layoutBuilder->setBlockTheme($templates);
        }

        return $layoutBuilder->getLayout($layoutContext);
    }

    /**
     * Add parameters to the layout context
     *
     * @param array            $parameters
     * @param LayoutAnnotation $layoutAnnotation
     * @param ContextInterface $layoutContext
     * @return ContextInterface
     */
    protected function prepareContext($parameters, $layoutAnnotation, $layoutContext = null)
    {
        if (is_null($layoutContext)) {
            $layoutContext = new LayoutContext();
        }

        $theme = $layoutAnnotation->getTheme();
        if (!empty($theme)) {
            if (isset($layoutContext[ThemeExtension::PARAM_THEME])) {
                $this->throwCannotRedefineOptionException(ThemeExtension::PARAM_THEME);
            }
            $layoutContext[ThemeExtension::PARAM_THEME] = $theme;
        }
        $action = $layoutAnnotation->getAction();
        if (!empty($action)) {
            if (isset($layoutContext['action'])) {
                $this->throwCannotRedefineOptionException('action');
            }
            $layoutContext['action'] = $action;
        }
        $vars = $layoutAnnotation->getVars();
        if (!empty($vars)) {
            $layoutContext->getDataResolver()->setRequired($vars);
        }

        foreach ($parameters as $key => $value) {
            $layoutContext[$key] = $value;
        }

        return $layoutContext;
    }

    /**
     * @param string $option
     * @throws LogicException
     */
    protected function throwCannotRedefineOptionException($option)
    {
        throw new LogicException(
            sprintf(
                'Layout annotation configured improperly.'
                . ' Cannot redefine context option %s that is already set in the response.',
                $option
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -120],
            KernelEvents::VIEW       => 'onKernelView',
        ];
    }
}
