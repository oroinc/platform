<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\BlockViewNotFoundException;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Checks whether a web request should be processed by the layout engine
 * (the Request object has the @Layout annotation in the "_layout" attribute),
 * and if so, renders the layout.
 */
class LayoutListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            LayoutManager::class,
            LoggerInterface::class
        ];
    }

    /**
     * @throws LogicException if @Layout annotation is used in incorrect way
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        /** @var LayoutAnnotation|null $layoutAnnotation */
        $layoutAnnotation = $request->attributes->get('_layout');
        if (null === $layoutAnnotation) {
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
        if (\is_array($parameters)) {
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
            $layoutManager = $this->container->get(LayoutManager::class);
            $layoutManager->getLayoutBuilder()->setBlockTheme($layoutAnnotation->getBlockThemes());
            $response = $this->getLayoutResponse($context, $request, $layoutManager);
        }

        $event->setResponse($response);
    }

    private function configureContext(ContextInterface $context, LayoutAnnotation $layoutAnnotation): void
    {
        $action = $layoutAnnotation->getAction();
        if ($action) {
            $currentAction = $context->getOr('action');
            if (empty($currentAction)) {
                $context->set('action', $action);
            }
        }

        $theme = $layoutAnnotation->getTheme();
        if ($theme) {
            $currentTheme = $context->getOr('theme');
            if (empty($currentTheme)) {
                $context->set('theme', $theme);
            }
        }
    }

    private function getLayoutResponse(
        ContextInterface $context,
        Request $request,
        LayoutManager $layoutManager
    ): Response {
        $blockIds = $request->get('layout_block_ids');
        if ($blockIds && \is_array($blockIds)) {
            $data = [];
            foreach ($blockIds as $blockId) {
                if ($blockId) {
                    try {
                        $data[$blockId] = $layoutManager->getLayout($context, $blockId)->render();
                    } catch (BlockViewNotFoundException $e) {
                        $this->logNotFoundViewException($blockId, $e);
                    }
                }
            }

            return new JsonResponse($data);
        }

        return new Response($layoutManager->getLayout($context)->render());
    }

    /**
     * @param string $blockId
     * @param BlockViewNotFoundException $e
     */
    private function logNotFoundViewException($blockId, BlockViewNotFoundException $e): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $logger->warning(
            sprintf('Unknown block "%s" was requested via layout_block_ids', $blockId),
            ['exception' => $e]
        );
    }
}
