<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\Fixture;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadJobExecutionData extends AbstractFixture
{
    /** @var array */
    protected $jobInstances = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadJobInstances($manager);
        $this->loadJobExecutions($manager);

        $manager->flush();
    }

    public function loadJobInstances(ObjectManager $manager)
    {
        $handle  = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'job_instance_data.csv', 'r');
        $headers = fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $combined = array_combine($headers, $data);

            $jobInstanceEntity = new JobInstance();
            $jobInstanceEntity->setCode($combined['Code']);
            $jobInstanceEntity->setAlias($combined['Alias']);
            $jobInstanceEntity->setStatus($combined['Status']);
            $jobInstanceEntity->setConnector($combined['Connector']);
            $jobInstanceEntity->setType($combined['Type']);

            $manager->persist($jobInstanceEntity);
            $this->jobInstances[$combined['Id']] = $jobInstanceEntity;
        }

        fclose($handle);
    }

    public function loadJobExecutions(ObjectManager $manager)
    {
        $handle  = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'job_execution_data.csv', 'r');
        $headers = fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $combined   = array_combine($headers, $data);
            $createTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $createTime->sub(\DateInterval::createFromDateString($combined['Create Time']));

            $jobExecutionEntity = new JobExecution();
            $jobExecutionEntity->setJobInstance($this->jobInstances[$combined['Job Instance']]);
            $jobExecutionEntity->setCreateTime($createTime);
            $jobExecutionEntity->setStatus(new BatchStatus($combined['Status']));
            $jobExecutionEntity->setPid($combined['Pid']);

            $manager->persist($jobExecutionEntity);
        }

        fclose($handle);
    }
}
