<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class LoadJobData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'command' => 'first_test_command',
            'state' => Job::STATE_FINISHED,
            'reference' => 'oro_integration:finished_job'
        ],
        [
            'command' => 'first_test_command',
            'state' => Job::STATE_FAILED,
            'reference' => 'oro_integration:failed_job'
        ],
        [
            'command' => 'first_test_command',
            'state' => Job::STATE_PENDING,
            'reference' => 'oro_integration:pending_job'
        ],
        [
            'command' => 'first_test_command',
            'state' => Job::STATE_RUNNING,
            'reference' => 'oro_integration:first_running_job'
        ],
        [
            'command' => 'first_test_command',
            'state' => Job::STATE_RUNNING,
            'reference' => 'oro_integration:second_running_job'
        ],
        [
            'command' => 'second_test_command',
            'state' => Job::STATE_RUNNING,
            'reference' => 'oro_integration:third_running_job'
        ],
        [
            'command' => 'second_test_command',
            'state' => Job::STATE_PENDING,
            'reference' => 'oro_integration:second_pending_job'
        ],
        [
            'command' => 'second_test_command',
            'state' => Job::STATE_PENDING,
            'reference' => 'oro_integration:third_pending_job'
        ],
        [
            'command' => 'second_test_command',
            'state' => Job::STATE_NEW,
            'reference' => 'oro_integration:second_new_job'
        ],
        [
            'command' => 'third_test_command',
            'state' => Job::STATE_RUNNING,
            'integration' => 'oro_integration:bar_integration',
            'reference' => 'oro_integration:running_job_for_bar_integration',
        ],
        [
            'command' => 'third_test_command',
            'state' => Job::STATE_RUNNING,
            'integration' => 'oro_integration:foo_integration',
            'reference' => 'oro_integration:running_job_for_foo_integration'
        ],
        [
            'command' => 'third_test_command',
            'state' => Job::STATE_RUNNING,
            'reference' => 'oro_integration:running_third_test_command_job'
        ],
        [
            'command' => 'third_test_command',
            'state' => Job::STATE_NEW,
            'reference' => 'oro_integration:new_third_test_command_job'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $reflection = new \ReflectionClass('JMS\JobQueueBundle\Entity\Job');

        foreach ($this->data as $item) {
            $args = [];
            if (!empty($item['integration'])) {
                /** @var Channel $integration */
                $integration = $this->getReference($item['integration']);
                $args[] = "--integration-id={$integration->getId()}";
            }

            $job = new Job($item['command'], $args);

            /**
             * Could not freely set state through setter
             */
            $stateProperty = $reflection->getProperty('state');
            $stateProperty->setAccessible(true);
            $stateProperty->setValue($job, $item['state']);

            $manager->persist($job);
            $this->setReference($item['reference'], $job);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData'
        ];
    }
}
