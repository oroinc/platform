<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WidgetConfigVisibilityFilter
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ResolverInterface             $resolver
     * @param FeatureChecker                $featureChecker
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ResolverInterface $resolver,
        FeatureChecker $featureChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
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

            if (!$this->isItemAllowed($widgetName, $itemName, $enabled, $acl, $applicable)) {
                continue;
            }

            $filteredItems[$itemName] = $item;
        }

        return $filteredItems;
    }

    /**
     * @param string|null $widgetName
     * @param string $itemName
     * @param bool $enabled
     * @param string|null $acl
     * @param string|null $applicable
     *
     * @return bool
     */
    protected function isItemAllowed($widgetName, $itemName, $enabled, $acl, $applicable)
    {
        if (!$enabled || ($acl && !$this->authorizationChecker->isGranted($acl))) {
            return false;
        }

        if ($applicable) {
            $resolved = $this->resolver->resolve([$applicable]);
            if (!reset($resolved)) {
                return false;
            }
        }

        $resource = $widgetName ? sprintf('%s.%s', $widgetName, $itemName) : $itemName;
        if (!$this->featureChecker->isResourceEnabled($resource, 'dashboard_widgets')) {
            return false;
        }

        return true;
    }
}
