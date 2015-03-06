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
     * Constructor.
     *
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
                || $layoutAnnotation->getVars()
                || $layoutAnnotation->getBlockThemes()
            ) {
                throw new LogicException(
                    '@Layout annotation configured improperly. Should use empty @Layout()'
                    . ' configuration when returning an instance of Oro\\Component\\Layout\\Layout in the response.'
                );
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

        if ($blockThemes = $layoutAnnotation->getBlockThemes()) {
            $layoutBuilder->setBlockTheme($blockThemes);
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
        $vars = $layoutAnnotation->getVars();
        if (!empty($vars)) {
            $layoutContext->getResolver()->setRequired($vars);
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
            KernelEvents::VIEW => 'onKernelView'
        ];
    }
}
