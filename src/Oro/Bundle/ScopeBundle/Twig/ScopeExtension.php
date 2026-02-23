<?php

namespace Oro\Bundle\ScopeBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to determine if the entity scope is empty:
 *   - oro_scope_is_empty
 *   - oro_scope_entities
 */
class ScopeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_scope_is_empty', [$this, 'isScopesEmpty']),
            new TwigFunction('oro_scope_entities', [$this, 'getScopeEntities']),
        ];
    }

    public function isScopesEmpty(array $scopeEntities, Collection $scopes): bool
    {
        if ($scopes->count() > 1) {
            return false;
        }

        $scope = $scopes->first();
        foreach ($scopeEntities as $fieldName => $class) {
            if (!empty($this->getPropertyAccessor()->getValue($scope, $fieldName))) {
                return false;
            }
        }

        return true;
    }

    public function getScopeEntities(string $scopeType): array
    {
        return $this->getScopeManager()->getScopeEntities($scopeType);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ScopeManager::class,
            PropertyAccessorInterface::class
        ];
    }

    private function getScopeManager(): ScopeManager
    {
        return $this->container->get(ScopeManager::class);
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->container->get(PropertyAccessorInterface::class);
    }
}
