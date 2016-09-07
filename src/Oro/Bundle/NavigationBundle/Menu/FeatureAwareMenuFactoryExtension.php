<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class FeatureAwareMenuFactoryExtension implements Factory\ExtensionInterface
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

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
        if (!$this->alreadyDenied($options) && !empty($options['route'])) {
            $options['extras']['isAllowed'] = $this->featureChecker
                ->isResourceEnabled($options['route'], 'routes');
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
    protected function alreadyDenied(array $options)
    {
        return array_key_exists('extras', $options) && array_key_exists('isAllowed', $options['extras']) &&
        ($options['extras']['isAllowed'] === false);
    }
}
