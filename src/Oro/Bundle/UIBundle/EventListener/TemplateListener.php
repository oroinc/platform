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

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $templateReference = $this->getTemplateReference($event);
        if ($templateReference) {
            $request = $event->getRequest();
            $twig = $this->getTwig();
            $this->resolveControllerDir($templateReference);
            $this->injectWidgetContainer($twig, $templateReference, $request);
            if (!$twig->getLoader()->exists($templateReference->template)) {
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
        if (
            !$template instanceof Template
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

        if (\is_string($template)) {
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

        foreach ($this->getFilesystemLoader()->getPaths($bundle) as $path) {
            if (
                file_exists(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $controller)
                && \in_array($controller, scandir($path), true)
            ) {
                return;
            }

            if (file_exists($path . DIRECTORY_SEPARATOR . $legacyController)) {
                $templateReference->template = \sprintf(
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

    private function injectWidgetContainer(Environment $twig, Template $templateReference, Request $request): void
    {
        $widgetContainer = $request->query->get('_widgetContainerTemplate')
            ?: $request->request->get('_widgetContainerTemplate')
            ?: $request->query->get('_widgetContainer')
            ?: $request->request->get('_widgetContainer');

        if ($widgetContainer) {
            if (!$this->processContainer($twig, $templateReference, $widgetContainer)) {
                $this->processContainer($twig, $templateReference, self::DEFAULT_CONTAINER);
            }
        }
    }

    /**
     * Check new template name based on container
     */
    private function processContainer(Environment $twig, Template $templateReference, string $container): bool
    {
        $parts = $this->parseTemplateReference($templateReference);
        if ($parts) {
            $templateName = $parts['path'] . $container . '/' . $parts['name'];
            if ($twig->getLoader()->exists($templateName)) {
                $templateReference->template = $templateName;
                return true;
            }

            if ($parts['widget']) {
                /**
                 * Checks if legacy template file is exists
                 */
                $templateName = $parts['path'] . $parts['widget'] . '/' . $container . '/' . $parts['template'];
                if ($twig->getLoader()->exists($templateName)) {
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            Inflector::class,
            Environment::class,
            FilesystemLoader::class
        ];
    }

    private function getTwig(): Environment
    {
        return $this->container->get(Environment::class);
    }

    private function getInflector(): Inflector
    {
        return $this->container->get(Inflector::class);
    }

    private function getFilesystemLoader(): FilesystemLoader
    {
        return $this->container->get(FilesystemLoader::class);
    }
}
