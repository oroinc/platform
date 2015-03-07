<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

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
     * @param LayoutManager $layoutManager
     */
    public function __construct(LayoutManager $layoutManager)
    {
        $this->layoutManager = $layoutManager;
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

        /** @var LayoutAnnotation|null $layoutAnnotation */
        $layoutAnnotation = $request->attributes->get('_layout');
        if (!$layoutAnnotation) {
            return;
        }

        $parameters = $event->getControllerResult();
        if (is_array($parameters)) {
            $context = new LayoutContext();
            foreach ($parameters as $key => $value) {
                $context->set($key, $value);
            }
            $this->configureContext($context, $layoutAnnotation);
            $layout = $this->getLayout($context, $layoutAnnotation);
        } elseif ($parameters instanceof ContextInterface) {
            $this->configureContext($parameters, $layoutAnnotation);
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
        $theme = $layoutAnnotation->getTheme();
        if (!empty($theme) && !$context->has(ThemeExtension::PARAM_THEME)) {
            $context->set(ThemeExtension::PARAM_THEME, $theme);
        }

        $vars = $layoutAnnotation->getVars();
        if (!empty($vars)) {
            $context->getResolver()->setRequired($vars);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onKernelView'
        ];
    }
}
