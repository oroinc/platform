<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Doctrine\Inflector\Inflector;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Resolve controller name and inject widget container template into guessed template path
 */
class TemplateListener implements ServiceSubscriberInterface
{
    private const DEFAULT_CONTAINER = 'widget';

    private ContainerInterface $container;

    private ?Environment $twig = null;

    private ?Inflector $inflector = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $templateReference = $this->getTemplateReference($event);
        $request = $event->getRequest();

        if ($templateReference) {
            $this->resolveControllerDir($templateReference);
            $this->injectWidgetContainer($templateReference, $request);

            if (!$this->getTwig()->getLoader()->exists($templateReference->template)) {
                $template = $request->attributes->get('_template');
                if ($template instanceof Template) {
                    $template->template = $templateReference->template;
                }
            }
        }
    }

    /**
     * Find template reference in request attributes
     */
    private function getTemplateReference(ViewEvent $event): ?Template
    {
        $template = $event->getRequest()->attributes->get('_template');
        if (!$template instanceof Template
            && !$template = $event->controllerArgumentsEvent?->getAttributes()[Template::class][0] ?? null
        ) {
            return null;
        }

        if ($template instanceof Template) {
            if (!$event->getRequest()->attributes->get('_template')) {
                $event->getRequest()->attributes->set('_template', $template);
            }

            return $template;
        }

        if (is_string($template)) {
            $parsedTemplate = new Template($template);
            $event->getRequest()->attributes->set('_template', $parsedTemplate);

            return $parsedTemplate;
        }

        return null;
    }

    /**
     * Allow to use the controller view directory name in CamelCase
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function resolveControllerDir(Template $templateReference): void
    {
        $parts = $this->parseTemplateReference($templateReference);
        if ($parts) {
            $bundle = $parts['bundle'];
            $controller = $parts['controller'];
        }

        if (!isset($bundle, $controller)) {
            return;
        }

        $legacyController = $this->getInflector()->classify($controller);
        if ($legacyController === $controller) {
            return;
        }

        $loader = $this->container->get('twig.loader.native_filesystem');
        foreach ($loader->getPaths($bundle) as $path) {
            if (file_exists(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $controller) &&
                in_array($controller, scandir($path), true)
            ) {
                return;
            }

            if (file_exists($path . DIRECTORY_SEPARATOR . $legacyController)) {
                $templateReference->template = sprintf(
                    '@%s/%s/%s',
                    $parts['bundle'],
                    $legacyController,
                    $this->resolveActionName($parts['name'])
                );

                return;
            }
        }
    }

    private function resolveActionName(string $name): string
    {
        return preg_match('/^(?<view>[^\/\.]+)(\.[a-z]+.[a-z]+)?$/', $name, $parsed)
            ? str_replace($parsed['view'], $this->getInflector()->camelize($parsed['view']), $name)
            : $name;
    }

    private function injectWidgetContainer(Template $templateReference, Request $request): void
    {
        $widgetContainer = $request->query->get('_widgetContainerTemplate')
            ?: $request->request->get('_widgetContainerTemplate')
            ?: $request->query->get('_widgetContainer')
            ?: $request->request->get('_widgetContainer');

        if ($widgetContainer) {
            if (!$this->processContainer($templateReference, $widgetContainer)) {
                $this->processContainer($templateReference, self::DEFAULT_CONTAINER);
            }
        }
    }

    /**
     * Check new template name based on container
     */
    private function processContainer(Template $templateReference, string $container): bool
    {
        $parts = $this->parseTemplateReference($templateReference);
        if ($parts) {
            $templateName = $parts['path'] . $container . '/' . $parts['name'];
            if ($this->getTwig()->getLoader()->exists($templateName)) {
                $templateReference->template = $templateName;
                return true;
            }

            if ($parts['widget']) {
                /**
                 * Checks if legacy template file is exists
                 */
                $templateName = $parts['path'] . $parts['widget'] . '/'. $container . '/' . $parts['template'];
                if ($this->getTwig()->getLoader()->exists($templateName)) {
                    $templateReference->template = $templateName;

                    return true;
                }
            }
        }

        return false;
    }

    private function parseTemplateReference(Template $template): ?array
    {
        if (!$template->template) {
            return null;
        }

        $pattern = '/^(?<path>@?(?<bundle>[^\/:]+)[\/:]{1}(?<controller>[^\/:]+)[\/:]{1})'
            . '((?<widget>.*)[\/:]{1})?(?<template>.*)$/';

        $parts = \preg_match($pattern, $template->template, $parts) ? $parts : null;
        if ($parts) {
            $parts['name'] = $parts['widget'] ? $parts['widget'] . '/' . $parts['template'] : $parts['template'];
        }
        return $parts;
    }

    private function getTwig(): Environment
    {
        if (!$this->twig) {
            $this->twig = $this->container->get(Environment::class);
        }

        return $this->twig;
    }

    private function getInflector(): Inflector
    {
        if (!$this->inflector) {
            $this->inflector = $this->container->get(Inflector::class);
        }

        return $this->inflector;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            Inflector::class,
            Environment::class,
            'twig.loader.native_filesystem' => FilesystemLoader::class,
        ];
    }
}
