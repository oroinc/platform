<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Doctrine\Inflector\Inflector;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateReferenceInterface;
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

    private ?TemplateNameParser $templateNameParser = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $templateReference = $this->getTemplateReference($request);
        if ($templateReference) {
            $this->resolveControllerDir($templateReference);
            $this->injectWidgetContainer($templateReference, $request);

            if (!$this->getTwig()->getLoader()->exists((string) $templateReference)) {
                $template = $request->attributes->get('_template');
                if ($template instanceof Template) {
                    $template->setTemplate((string) $templateReference);
                }
            }
        }
    }

    /**
     * Find template reference in request attributes
     */
    private function getTemplateReference(Request $request): ?TemplateReferenceInterface
    {
        $template = $request->attributes->get('_template');

        if ($template instanceof TemplateReferenceInterface) {
            return $template;
        }

        if ($template instanceof Template) {
            $templateReference = $template->getTemplate();

            if ($templateReference instanceof TemplateReferenceInterface) {
                return $templateReference;
            }

            if (is_string($templateReference)) {
                $parsedTemplateReference = $this->getTemplateNameParser()->parse($templateReference);
                $template->setTemplate($parsedTemplateReference);
                return $parsedTemplateReference;
            }
        }

        if (is_string($template)) {
            $parsedTemplateReference = $this->getTemplateNameParser()->parse($template);
            $request->attributes->set('_template', $parsedTemplateReference);
            return $parsedTemplateReference;
        }

        return null;
    }

    /**
     * Allow to use the controller view directory name in CamelCase
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function resolveControllerDir(TemplateReferenceInterface $templateReference): void
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
                $templateReference->set('name', sprintf(
                    '@%s/%s/%s',
                    $parts['bundle'],
                    $legacyController,
                    $this->resolveActionName($parts['name'])
                ));

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

    private function injectWidgetContainer(TemplateReferenceInterface $templateReference, Request $request): void
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
    private function processContainer(TemplateReferenceInterface $templateReference, string $container): bool
    {
        $parts = $this->parseTemplateReference($templateReference);
        if ($parts) {
            $templateName = $parts['path'] . $container . '/' . $parts['name'];
            if ($this->getTwig()->getLoader()->exists($templateName)) {
                $templateReference->set('name', $templateName);
                return true;
            }

            if ($parts['widget']) {
                /**
                 * Checks if legacy template file is exists
                 */
                $templateName = $parts['path'] . $parts['widget'] . '/'. $container . '/' . $parts['template'];
                if ($this->getTwig()->getLoader()->exists($templateName)) {
                    $templateReference->set('name', $templateName);

                    return true;
                }
            }
        }

        return false;
    }

    private function parseTemplateReference(TemplateReferenceInterface $templateReference): ?array
    {
        $parameters = $templateReference->all();
        if (\count($parameters) !== 2 || !isset($parameters['name'], $parameters['engine'])) {
            return null;
        }

        $pattern = '/^(?<path>@?(?<bundle>[^\/:]+)[\/:]{1}(?<controller>[^\/:]+)[\/:]{1})'
            . '((?<widget>.*)[\/:]{1})?(?<template>.*)$/';

        $parts = \preg_match($pattern, $parameters['name'], $parts) ? $parts : null;
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

    private function getTemplateNameParser(): TemplateNameParser
    {
        if (!$this->templateNameParser) {
            $this->templateNameParser = $this->container->get(TemplateNameParser::class);
        }

        return $this->templateNameParser;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            Inflector::class,
            Environment::class,
            TemplateNameParser::class,
            'twig.loader.native_filesystem' => FilesystemLoader::class,
        ];
    }
}
