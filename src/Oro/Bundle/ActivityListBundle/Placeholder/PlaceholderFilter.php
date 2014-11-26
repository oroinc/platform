<?php

namespace Oro\Bundle\ActivityListBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

class PlaceholderFilter
{
    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    public function __construct(ActivityListChainProvider $activityListChainProvider)
    {
        $this->activityListProvider = $activityListChainProvider;
    }

    /**
     * Checks if the entity can have activities
     *
     * @param object|null $entity
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        return in_array(ClassUtils::getClass($entity), $this->activityListProvider->getTargetEntityClasses());
    }
}
