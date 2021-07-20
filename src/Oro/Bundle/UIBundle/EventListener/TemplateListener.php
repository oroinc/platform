<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Doctrine\Inflector\Inflector;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Resolve controller name and inject widget container template into guessed template path
 */
class TemplateListener implements ServiceSubscriberInterface
{
    private const DEFAULT_CONTAINER = 'widget';

    /** @var ContainerInterface */
    private $container;

    /** @var EngineInterface */
    private $templating;
    private Inflector $inflector;

    public function __construct(ContainerInterface $container, Inflector $inflector)
    {
        $this->container = $container;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        $templateReference = $this->getTemplateReference($request);
        if ($templateReference) {
            $this->resolveControllerDir($templateReference);
            $this->injectWidgetContainer($templateReference, $request);
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
                $parsedTemplateReference = $this->container->get('templating.name_parser')->parse($templateReference);
                $template->setTemplate($parsedTemplateReference);
                return $parsedTemplateReference;
            }
        }

        if (is_string($template)) {
            $parsedTemplateReference = $this->container->get('templating.name_parser')->parse($template);
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
        if ($templateReference instanceof TemplateReference) {
            $bundle = $templateReference->get('bundle');
            $controller = $templateReference->get('controller');
        } else {
            $parts = $this->parseTemplateReference($templateReference);
            if ($parts) {
                $bundle = $parts['bundle'];
                $controller = $parts['controller'];
            }
        }

        if (!isset($bundle, $controller)) {
            return;
        }

        $legacyController = $this->inflector->classify($controller);
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
                if ($templateReference instanceof TemplateReference) {
                    $templateReference->set('controller', $legacyController);
                    $templateReference->set('name', $this->resolveActionName($templateReference->get('name')));
                } else {
                    $templateReference->set('name', sprintf(
                        '@%s/%s/%s',
                        $parts['bundle'],
                        $legacyController,
                        $this->resolveActionName($parts['name'])
                    ));
                }

                return;
            }
        }
    }

    private function resolveActionName(string $name): string
    {
        return preg_match('/^(?<view>[^\/\.]+)(\.[a-z]+.[a-z]+)?$/', $name, $parsed)
            ? str_replace($parsed['view'], $this->inflector->camelize($parsed['view']), $name)
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
        if ($templateReference instanceof TemplateReference) {
            $templateName = sprintf(
                '%s:%s:%s.%s.%s',
                $templateReference->get('bundle'),
                $templateReference->get('controller'),
                $container . '/' . $templateReference->get('name'),
                $templateReference->get('format'),
                $templateReference->get('engine')
            );

            if ($this->getTemplating()->exists($templateName)) {
                $templateReference->set('name', $container . '/' . $templateReference->get('name'));
                return true;
            }
        } else {
            $parts = $this->parseTemplateReference($templateReference);
            if ($parts) {
                $templateName = $parts['path'] . $container . '/' . $parts['name'];

                if ($this->getTemplating()->exists($templateName)) {
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

        $pattern = '/^(?<path>@?(?<bundle>[^\/:]+)[\/:]{1}(?<controller>[^\/:]+)[\/:]{1})(?<name>.+)$/';

        return \preg_match($pattern, $parameters['name'], $parts) ? $parts : null;
    }

    private function getTemplating(): EngineInterface
    {
        if (!$this->templating) {
            $this->templating = $this->container->get('templating');
        }

        return $this->templating;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'templating' => EngineInterface::class,
            'twig.loader.native_filesystem' => FilesystemLoader::class,
            'templating.name_parser' => TemplateNameParserInterface::class
        ];
    }
}
