<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Adding widget container template into guessed template path
 */
class TemplateListener implements ServiceSubscriberInterface
{
    private const DEFAULT_CONTAINER = 'widget';

    /** @var ContainerInterface */
    private $container;

    /** @var EngineInterface */
    private $templating;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();

        $widgetContainer = $request->query->get('_widgetContainerTemplate')
            ?: $request->request->get('_widgetContainerTemplate')
            ?: $request->query->get('_widgetContainer')
            ?: $request->request->get('_widgetContainer');

        if ($widgetContainer) {
            $template = $this->getTemplateReference($request);
            if ($template) {
                if (!$this->processContainer($template, $widgetContainer)) {
                    $this->processContainer($template, self::DEFAULT_CONTAINER);
                }
            }
        }
    }

    /**
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
     * Check new template name based on container
     *
     * @param TemplateReferenceInterface $template
     * @param string $container
     * @return bool
     */
    private function processContainer(TemplateReferenceInterface $template, string $container): bool
    {
        if ($template instanceof TemplateReference) {
            $templateName = sprintf(
                '%s:%s:%s.%s.%s',
                $template->get('bundle'),
                $template->get('controller'),
                $container . '/' . $template->get('name'),
                $template->get('format'),
                $template->get('engine')
            );

            if ($this->getTemplating()->exists($templateName)) {
                $template->set('name', $container . '/' . $template->get('name'));
                return true;
            }
        } else {
            $parameters = $template->all();
            if (count($parameters) === 2
                && isset($parameters['name'], $parameters['engine'])
                && preg_match('/^(?<path>@?[^\/:]+[\/:]{1}[^\/:]+[\/:]{1})(?<name>.+)$/', $parameters['name'], $parts)
            ) {
                $templateName = $parts['path'] . $container . '/' . $parts['name'];

                if ($this->getTemplating()->exists($templateName)) {
                    $template->set('name', $templateName);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return EngineInterface
     */
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
            'templating.name_parser' => TemplateNameParserInterface::class
        ];
    }
}
