<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes the entity config pages of the given entities respond with 404
 *
 * A workaround for BB-27972, see `ExcludedEntitiesConfigGridListener`. Note that `mode: 'hidden'` does not
 * close these pages, so this listener is needed even after BB-27972 unless it closes them as well.
 */
class ExcludedEntitiesConfigRequestListener
{
    /**
     * The entity config pages are served by the routes of these namespaces only, so a request to any other route
     * is skipped without looking at the resolved controller arguments.
     */
    private const ROUTE_PREFIXES = ['oro_entityconfig_', 'oro_entityextend_', 'oro_attribute_'];

    /**
     * @param string[] $excludedEntities
     */
    public function __construct(private readonly array $excludedEntities)
    {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$this->excludedEntities || !$this->isEntityConfigRequest($event->getRequest())) {
            return;
        }

        foreach ($event->getArguments() as $argument) {
            if ($argument instanceof FieldConfigModel) {
                $argument = $argument->getEntity();
            }
            if (
                $argument instanceof EntityConfigModel
                && \in_array($argument->getClassName(), $this->excludedEntities, true)
            ) {
                throw new NotFoundHttpException(
                    \sprintf('The "%s" entity is not available in the entity management.', $argument->getClassName())
                );
            }
        }
    }

    private function isEntityConfigRequest(Request $request): bool
    {
        $route = $request->attributes->get('_route');
        if (!\is_string($route)) {
            return false;
        }

        foreach (self::ROUTE_PREFIXES as $routePrefix) {
            if (str_starts_with($route, $routePrefix)) {
                return true;
            }
        }

        return false;
    }
}
