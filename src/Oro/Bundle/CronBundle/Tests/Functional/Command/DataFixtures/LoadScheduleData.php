<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CronBundle\Entity\Schedule;

class LoadScheduleData extends AbstractFixture
{
    /** @var array */
    protected $schedules = [
        [
            'command' => 'oro:test',
            'arguments' => [],
            'definition' => '*/1 * * * *'
        ],
        [
            'command' => 'oro:cron:cleanup',
            'arguments' => ['--dry-run'],
            'definition' => '* */5 * * *'
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->schedules as $config) {
            $schedule = new Schedule();
            $schedule
                ->setCommand($config['command'])
                ->setArguments($config['arguments'])
                ->setDefinition($config['definition']);

            $manager->persist($schedule);
        }

        $manager->flush();
    }
}
