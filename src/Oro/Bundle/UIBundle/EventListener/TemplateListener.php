<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Templating\TemplateReferenceInterface;

class TemplateListener
{
    const TEMPLATE_PARTS_SEPARATOR = ':';
    const DEFAULT_CONTAINER = 'widget';

    /** @var ContainerInterface */
    protected $container;

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
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        $container = $request->query->get(
            '_widgetContainerTemplate',
            $request->request->get('_widgetContainerTemplate')
        );
        if (!$container) {
            $container = $request->query->get('_widgetContainer', $request->request->get('_widgetContainer'));
        }

        if ($container) {
            $template = $request->attributes->get('_template');
            if ($template instanceof TemplateReferenceInterface) {
                $template = $template->getLogicalName();
            } elseif ($template instanceof Template) {
                $template = $template->getTemplate();
            }
            if (strpos($template, self::TEMPLATE_PARTS_SEPARATOR) !== false) {
                $templateParts = explode(self::TEMPLATE_PARTS_SEPARATOR, $template);
                if ($templateParts) {
                    $containerTemplate = $this->getTemplateName($templateParts, $container);
                    /** @var EngineInterface $templating */
                    $templating = $this->container->get('templating');
                    if ($templating->exists($containerTemplate)) {
                        $request->attributes->set('_template', $containerTemplate);
                    } else {
                        $widgetTemplate = $this->getTemplateName($templateParts, self::DEFAULT_CONTAINER);
                        if ($templating->exists($widgetTemplate)) {
                            $request->attributes->set('_template', $widgetTemplate);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get new template name based on container
     *
     * @param array $parts
     * @param string $container
     * @return string
     */
    protected function getTemplateName(array $parts, $container)
    {
        $partsCount = count($parts);
        $parts[$partsCount - 1] = $container . '/' . $parts[$partsCount - 1];

        return implode(self::TEMPLATE_PARTS_SEPARATOR, $parts);
    }
}
