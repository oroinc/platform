<?php

namespace Oro\Bundle\CronBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\CronBundle\Entity\Repository\JobRepository;

class LoadClassMetadataSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        if ($metadata->name === Job::class) {
            $metadata->customRepositoryClassName = JobRepository::class;
        }
    }
}
