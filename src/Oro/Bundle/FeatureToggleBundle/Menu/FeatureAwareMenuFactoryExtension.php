<?php

namespace Oro\Bundle\FeatureToggleBundle\Menu;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Disallows menu items that related to routes disabled by a feature.
 */
class FeatureAwareMenuFactoryExtension implements ExtensionInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options = [])
    {
        if (!empty($options['route'])
            && !$this->alreadyDenied($options)
            && !$this->featureChecker->isResourceEnabled($options['route'], 'routes')
        ) {
            $options['extras']['isAllowed'] = false;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * @param array $options
     * @return bool
     */
    private function alreadyDenied(array $options)
    {
        return
            array_key_exists('extras', $options)
            && array_key_exists('isAllowed', $options['extras'])
            && false === $options['extras']['isAllowed'];
    }
}
