<?php

namespace Oro\Bundle\FeatureToggleBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to check feature status:
 *   - feature_enabled
 *   - feature_resource_enabled
 */
class FeatureExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('feature_enabled', [$this, 'isFeatureEnabled']),
            new TwigFunction('feature_resource_enabled', [$this, 'isResourceEnabled']),
        ];
    }

    /**
     * @param string $feature
     * @param int|object|null $scopeIdentifier
     * @return bool
     */
    public function isFeatureEnabled($feature, $scopeIdentifier = null)
    {
        return $this->getFeatureChecker()->isFeatureEnabled($feature, $scopeIdentifier);
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @param int|object|null $scopeIdentifier
     * @return bool
     */
    public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        return $this->getFeatureChecker()->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            FeatureChecker::class
        ];
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }
}
