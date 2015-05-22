<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;

use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

class LoadJmsJobs extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 5; $i++) {
            $job = new Job('command', [], false); // Job in status new

            $manager->persist($job);
        }

        $manager->flush();
    }
}
