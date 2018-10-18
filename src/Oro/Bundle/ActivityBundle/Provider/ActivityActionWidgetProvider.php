<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

/**
 * Provider for activity action widget.
 */
class ActivityActionWidgetProvider implements WidgetProviderInterface
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var PlaceholderProvider */
    protected $placeholderProvider;

    /**
     * @param ActivityManager     $activityManager
     * @param PlaceholderProvider $placeholderProvider
     */
    public function __construct(
        ActivityManager $activityManager,
        PlaceholderProvider $placeholderProvider
    ) {
        $this->activityManager     = $activityManager;
        $this->placeholderProvider = $placeholderProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return is_object($object) && $this->activityManager->hasActivityAssociations(ClassUtils::getClass($object));
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($object)
    {
        $result = [];

        $entityClass = ClassUtils::getClass($object);

        $items = $this->activityManager->getActivityActions($entityClass);
        foreach ($items as $item) {
            $buttonWidget = $this->placeholderProvider->getItem($item['button_widget'], ['entity' => $object]);
            if ($buttonWidget) {
                $widget = [
                    'name'   => $item['button_widget'],
                    'button' => $buttonWidget
                ];
                if (!empty($item['link_widget'])) {
                    $linkWidget = $this->placeholderProvider->getItem($item['link_widget'], ['entity' => $object]);
                    if ($linkWidget) {
                        $widget['link'] = $linkWidget;
                    }
                }
                if (isset($item['group'])) {
                    $widget['group'] = $item['group'];
                }
                if (isset($item['priority'])) {
                    $widget['priority'] = $item['priority'];
                }
                $result[] = $widget;
            }
        }

        return $result;
    }
}
