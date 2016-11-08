<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Config\Resolver\ResolverInterface;

class WidgetConfigVisibilityFilter
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param SecurityFacade $securityFacade
     * @param ResolverInterface $resolver
     * @param FeatureChecker $featureChecker
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ResolverInterface $resolver,
        FeatureChecker $featureChecker
    ) {
        $this->securityFacade = $securityFacade;
        $this->resolver = $resolver;
        $this->featureChecker = $featureChecker;
    }

    /**
     * Filter visible widget items
     *
     * @param array       $configs
     * @param string|null $widgetName Name of widget in case $items are sub widgets of the widget
     *
     * @return array
     */
    public function filterConfigs(array $configs, $widgetName = null)
    {
        $filteredItems = [];
        foreach ($configs as $itemName => $item) {
            $acl        = isset($item['acl']) ? $item['acl'] : null;
            $applicable = isset($item['applicable']) ? $item['applicable'] : null;
            $enabled    = $item['enabled'];
            unset($item['acl'], $item['applicable'], $item['enabled']);

            if (!$this->isItemAllowed($widgetName, $itemName, $item, $enabled, $acl, $applicable)) {
                continue;
            }

            $filteredItems[$itemName] = $item;
        }

        return $filteredItems;
    }

    /**
     * @param string|null $widgetName
     * @param string $itemName
     * @param array $item
     * @param bool $enabled
     * @param string|null $acl
     * @param string|null $applicable
     *
     * @return bool
     */
    protected function isItemAllowed($widgetName, $itemName, $item, $enabled, $acl, $applicable)
    {
        if (!$enabled || ($acl && !$this->securityFacade->isGranted($acl))) {
            return false;
        }

        if ($applicable) {
            $resolved = $this->resolver->resolve([$applicable]);
            if (!reset($resolved)) {
                return false;
            }
        }

        $resource = $widgetName ? sprintf('%s.%s', $widgetName, $itemName) : $itemName;
        if (!$this->featureChecker->isResourceEnabled($resource, 'dashboards')) {
            return false;
        }

        if (isset($item['route']) && !$this->featureChecker->isResourceEnabled($item['route'], 'routes')) {
            return false;
        }

        return true;
    }
}
