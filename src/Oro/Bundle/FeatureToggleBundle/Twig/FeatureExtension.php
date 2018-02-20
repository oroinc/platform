<?php

namespace Oro\Bundle\FeatureToggleBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FeatureExtension extends \Twig_Extension
{
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
     * @return FeatureChecker
     */
    protected function getFeatureChecker()
    {
        return $this->container->get('oro_featuretoggle.checker.feature_checker');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('feature_enabled', [$this, 'isFeatureEnabled']),
            new \Twig_SimpleFunction('feature_resource_enabled', [$this, 'isResourceEnabled']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_featuretoggle_extension';
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
}
