<?php

namespace Oro\Bundle\TrackingBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\TrackingBundle\Command\AggregateCommand;

class ConfigPrecalculateListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        $statisticToggleKey = 'oro_tracking.precalculated_statistic_enabled';
        if (($event->isChanged($statisticToggleKey) && $event->getNewValue($statisticToggleKey))
            || $event->isChanged('oro_locale.timezone')
        ) {
            $job = new Job(AggregateCommand::COMMAND_NAME);
            $em = $this->registry->getManagerForClass(Job::class);
            $em->persist($job);
            $em->flush($job);
        }
    }
}
