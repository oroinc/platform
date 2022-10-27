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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return FeatureChecker
     */
    protected function getFeatureChecker()
    {
        return $this->container->get(FeatureChecker::class);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            FeatureChecker::class,
        ];
    }
}
