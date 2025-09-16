<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\EventListener;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overrides "_template" request attribute giving an ability to override the controller template
 * via "_template_override" option in route definition.
 */
class ControllerTemplateListener implements EventSubscriberInterface
{
    private string $attributeName = '_template_override';

    public function setAttributeName(string $attributeName): void
    {
        $this->attributeName = $attributeName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(KernelEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->attributes->has($this->attributeName)) {
            $templateOverride = $request->attributes->get($this->attributeName);
            if (!$templateOverride instanceof Template) {
                $templateOverride = new Template($templateOverride);
            }

            $request->attributes->set('_template', $templateOverride);
        }
    }
}
