<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadDoctrineJobRepositoryData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager = $this->container->get('oro_batch.job.repository')->getJobManager();

        $jobInstance = new JobInstance();
        $jobInstance->setCode('test_job_instance_1');
        $jobInstance->setAlias('test_alias_1');
        $jobInstance->setStatus(JobInstance::STATUS_READY);
        $jobInstance->setConnector('test_connector');
        $jobInstance->setType(JobInstance::TYPE_EXPORT);
        $manager->persist($jobInstance);
        $this->setReference('job_instance_1', $jobInstance);

        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);
        $jobExecution->setCreateTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $jobExecution->setStatus(new BatchStatus(BatchStatus::STARTED));
        $jobExecution->setPid(123);
        $manager->persist($jobExecution);
        $this->setReference('job_execution_1', $jobExecution);

        $stepExecution = new StepExecution('test_step_execution_1', $jobExecution);
        $stepExecution->setStatus(new BatchStatus(BatchStatus::STARTED));
        $manager->persist($stepExecution);
        $this->setReference('step_execution_1', $stepExecution);

        $manager->flush();
    }
}
