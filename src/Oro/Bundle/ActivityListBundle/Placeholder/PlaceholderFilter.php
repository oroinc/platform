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
     * @param object $entity
     * @return bool
     */
    public function isApplicable($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);

        return in_array(
            $className,
            $this->activityListProvider->getTargetEntityClasses()
        );
    }
}
