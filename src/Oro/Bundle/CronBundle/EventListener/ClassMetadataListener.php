<?php

/**
 * @deprecated Since 1.11, will be removed after 1.13.
 *
 * @TODO:
 * Remove this file after BAP-10703 implementation or
 * after migration from jms/job-queue-bundle 1.2.* to jms/job-queue-bundle 1.3.*
 *
 * This fix brings performance optimization of JobRepository which was introduced in
 * jms/job-queue-bundle 1.3.0. As of there are other stories to upgrade jms/job-queue-bundle version
 * or replace it, this solution is temporary.
 */
namespace Oro\Bundle\CronBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

class ClassMetadataListener
{
    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /* @var $classMetadata \Doctrine\ORM\Mapping\ClassMetadata */
        $classMetadata = $eventArgs->getClassMetadata();
        if ('JMS\JobQueueBundle\Entity\Job' === $classMetadata->name) {
            $classMetadata->customRepositoryClassName = 'Oro\Bundle\CronBundle\Entity\Repository\JobRepository';
        }
    }
}
