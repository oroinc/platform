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

    private Inflector $inflector;

    private static ?TemplateNameParser $templateNameParser = null;

    public function __construct(ContainerInterface $container, Inflector $inflector)
    {
        $this->container = $container;
        $this->inflector = $inflector;
    }

    public function onKernelView(ViewEvent $event): void
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
     *
     * @param Request $request
     * @return TemplateReferenceInterface|null
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
     * @param TemplateReferenceInterface $templateReference
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

    /**
     * @param string $name
     * @return string
     */
    private function resolveActionName(string $name): string
    {
        return preg_match('/^(?<view>[^\/\.]+)(\.[a-z]+.[a-z]+)?$/', $name, $parsed)
            ? str_replace($parsed['view'], $this->inflector->camelize($parsed['view']), $name)
            : $name;
    }

    /**
     * @param TemplateReferenceInterface $templateReference
     * @param Request $request
     */
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
     *
     * @param TemplateReferenceInterface $templateReference
     * @param string $container
     * @return bool
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
        }

        return false;
    }

    /**
     * @param TemplateReferenceInterface $templateReference
     * @return array|null
     */
    private function parseTemplateReference(TemplateReferenceInterface $templateReference): ?array
    {
        $parameters = $templateReference->all();
        if (\count($parameters) !== 2 || !isset($parameters['name'], $parameters['engine'])) {
            return null;
        }

        $pattern = '/^(?<path>@?(?<bundle>[^\/:]+)[\/:]{1}(?<controller>[^\/:]+)[\/:]{1})(?<name>.+)$/';

        return \preg_match($pattern, $parameters['name'], $parts) ? $parts : null;
    }

    /**
     * @return Environment
     */
    private function getTwig(): Environment
    {
        if (!$this->twig) {
            $this->twig = $this->container->get('twig');
        }

        return $this->twig;
    }

    /**
     * @return TemplateNameParser
     */
    private function getTemplateNameParser(): TemplateNameParser
    {
        if (!static::$templateNameParser) {
            static::$templateNameParser = new TemplateNameParser();
        }

        return static::$templateNameParser;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'twig' => Environment::class,
            'twig.loader.native_filesystem' => FilesystemLoader::class,
        ];
    }
}
