<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\Job;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\BatchBundle\Tests\Functional\Fixture\LoadDoctrineJobRepositoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class DoctrineJobRepositoryTest extends WebTestCase
{
    /** @var DoctrineJobRepository */
    private $doctrineJobRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->doctrineJobRepository = $this->getContainer()->get('oro_batch.job.repository');
        $this->loadFixtures([LoadDoctrineJobRepositoryData::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->doctrineJobRepository->getJobManager();
    }

    private function findStepExecution(string $stepName): ?StepExecution
    {
        return $this->doctrineJobRepository->getJobManager()
            ->getRepository(StepExecution::class)
            ->createQueryBuilder('s')
            ->where('s.stepName = :stepName')
            ->setParameter('stepName', $stepName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function entityManagerStateDataProvider(): array
    {
        return [
            'not closed entity manager' => [false],
            //'closed entity manager'     => [true],
        ];
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testUpdateStepExecution(bool $closed)
    {
        /** @var StepExecution $stepExecution */
        $stepExecution = $this->getReference('step_execution_1');
        $stepExecution->setStatus(new BatchStatus(BatchStatus::FAILED));

        if ($closed) {
            $this->doctrineJobRepository->getJobManager()->close();
        }
        $this->doctrineJobRepository->updateStepExecution($stepExecution);

        $this->doctrineJobRepository->getJobManager()->clear();
        $updatedStepExecution = $this->findStepExecution($stepExecution->getStepName());

        self::assertEquals(BatchStatus::FAILED, $updatedStepExecution->getStatus()->getValue());
    }

    public function testUpdateStepExecutionWhenTransactionIsRolledBackButEntityManagerIsNotClosed()
    {
        /** @var StepExecution $stepExecution */
        $stepExecution = $this->getReference('step_execution_1');
        $stepExecution->setStatus(new BatchStatus(BatchStatus::FAILED));

        $this->doctrineJobRepository->getJobManager()->rollback();
        $this->doctrineJobRepository->updateStepExecution($stepExecution);

        $this->doctrineJobRepository->getJobManager()->clear();

        // the step execution should not be found because we updated a record that does not exist in DB
        self::assertNull($this->findStepExecution($stepExecution->getStepName()));
    }
}
