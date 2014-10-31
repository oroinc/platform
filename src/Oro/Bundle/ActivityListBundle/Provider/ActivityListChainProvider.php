<?php

namespace Oro\Bundle\ActivityListBundle\Provider;


use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\EventListener\ActivityListListener;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;

class ActivityListChainProvider
{
    /**
     * @var ActivityListProviderInterface[]
     */
    protected $providers;

    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    public function getActivityListByActivityEntity($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                $list = new ActivityList();
                $list->setVerb(ActivityListListener::STATE_CREATE);
                $list->setRelatedActivityClass($provider->getActivityClass());
                //$list->setRelatedActivityId($provider->get)
            }
        }
    }
} 