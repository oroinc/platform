<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager;
use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

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
    public function supports($entity)
    {
        return $this->activityManager->hasActivityAssociations(ClassUtils::getClass($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        $entityClass = ClassUtils::getClass($entity);

        $items = $this->activityManager->getActivityActions($entityClass);
        foreach ($items as $item) {
            $widget = $this->placeholderProvider->getItem($item['widget'], ['entity' => $entity]);
            if ($widget) {
                $widget['name'] = $item['widget'];
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
