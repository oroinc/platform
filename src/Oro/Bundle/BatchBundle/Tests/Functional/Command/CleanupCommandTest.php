<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\Command;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Tests\Functional\Fixture\LoadJobExecutionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

class CleanupCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadJobExecutionData::class]);
    }

    /**
     * @dataProvider paramProvider
     */
    public function testCommandOutput(string $expectedContent, array $params): void
    {
        $result = self::runCommand('oro:cron:batch:cleanup', $params);
        self::assertStringContainsString($expectedContent, $result);

        $fileName = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixture'
            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_results.yml';

        $expectedResults = Yaml::parse(file_get_contents($fileName));
        if (isset($params['-i'], $expectedResults['data'][$params['-i']])) {
            $expectedJobData  = $expectedResults['data'][$params['-i']];
            $jobInstanceCodes = $this->getEntityFieldAsArray(JobInstance::class, 'code');
            $jobExecutionPids = $this->getEntityFieldAsArray(JobExecution::class, 'pid');
            self::assertEquals($expectedJobData['job_instance_codes'], $jobInstanceCodes);
            self::assertEquals(explode(',', $expectedJobData['job_execution_pids']), $jobExecutionPids);
        }
    }

    public function paramProvider(): array
    {
        return [
            'should show help'                             => [
                '$expectedContent' => 'Usage: oro:cron:batch:cleanup [options]',
                '$params'          => ['--help']
            ],
            'should show no records found'                 => [
                '$expectedContent' => 'There are no jobs eligible for clean up',
                '$params'          => ['-i' => '1 year']
            ],
            'should show success output and records count' => [
                '$expectedContent' => 'Batch jobs will be deleted: 6 Batch job history cleanup complete',
                '$params'          => ['-i' => '2 weeks']
            ]
        ];
    }

    private function getEntityFieldAsArray(string $repositoryName, string $field): array
    {
        /** @var QueryBuilder $qb */
        $qb = self::getContainer()->get('oro_batch.job.repository')
            ->getJobManager()
            ->getRepository($repositoryName)
            ->createQueryBuilder('i');

        $items = $qb->select('i.' . $field)
            ->orderBy('i.' . $field)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn ($item) => $item[$field], $items);
    }
}
