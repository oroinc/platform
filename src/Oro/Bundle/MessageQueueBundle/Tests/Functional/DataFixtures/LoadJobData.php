<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadJobData extends AbstractFixture implements ContainerAwareInterface
{
    use EntityTrait;

    const JOB_1 = 'job.1';
    const JOB_2 = 'job.2';
    const JOB_3 = 'job.3';
    const JOB_4 = 'job.4';
    const JOB_5 = 'job.5';
    const JOB_6 = 'job.6';
    const JOB_7 = 'job.7';
    const JOB_8 = 'job.8';
    const JOB_9 = 'job.9';

    /** @var ContainerInterface */
    private $container;

    /** @var array */
    private static $jobs = [
        self::JOB_1 => [
            'name' => self::JOB_1,
            'owner_id' => 'owner-id-1',
            'unique' => true,
            'status' => Job::STATUS_NEW
        ],
        self::JOB_2 => [
            'name' => self::JOB_2,
            'status' => Job::STATUS_NEW,
            'root_job' => self::JOB_1
        ],
        self::JOB_3 => [
            'name' => self::JOB_3,
            'owner_id' => 'owner-id-3',
            'status' => Job::STATUS_NEW
        ],
        self::JOB_4 => [
            'name' => self::JOB_4,
            'status' => Job::STATUS_NEW,
            'root_job' => self::JOB_3
        ],
        self::JOB_5 => [
            'name' => self::JOB_5,
            'owner_id' => 'owner-id-5',
            'unique' => true,
            'status' => Job::STATUS_NEW,
        ],
        self::JOB_6 => [
            'name' => self::JOB_6,
            'status' => Job::STATUS_NEW,
            'root_job' => self::JOB_5
        ],
        self::JOB_7 => [
            'name' => self::JOB_7,
            'status' => Job::STATUS_RUNNING,
            'root_job' => self::JOB_5
        ],
        self::JOB_8 => [
            'name' => self::JOB_8,
            'status' => Job::STATUS_CANCELLED,
            'root_job' => self::JOB_5
        ],
        self::JOB_9 => [
            'name' => self::JOB_9,
            'status' => Job::STATUS_NEW,
            'root_job' => self::JOB_5
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $jobStorage = $this->container->get('oro_message_queue.job.storage');
        foreach (self::$jobs as $jobReference => $data) {
            $data['created_at'] = new \DateTime('now', new \DateTimeZone('UTC'));
            if (array_key_exists('root_job', $data)) {
                $data['root_job'] = $this->getReference($data['root_job']);
            }
            $entity = $this->getEntity(Job::class, $data);
            $this->setReference($jobReference, $entity);
            $jobStorage->saveJob($entity);
        }
    }
}
