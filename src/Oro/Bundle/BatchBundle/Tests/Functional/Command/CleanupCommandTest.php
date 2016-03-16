<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\Command;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CleanupCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\BatchBundle\Tests\Functional\Fixture\LoadJobExecutionData']);
    }

    /**
     * @dataProvider paramProvider
     *
     * @param string $expectedContent
     * @param array  $params
     */
    public function testCommandOutput($expectedContent, $params)
    {
        $result = $this->runCommand('oro:cron:batch:cleanup', $params);
        $this->assertContains($expectedContent, $result);

        $fileName = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixture'
            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_results.yml';

        $expectedResults = Yaml::parse(file_get_contents($fileName));
        if (isset($params['-i']) && isset($expectedResults['data'][$params['-i']])) {
            $expectedJobData  = $expectedResults['data'][$params['-i']];
            $jobInstanceCodes = $this->getEntityFieldAsArray('AkeneoBatchBundle:JobInstance', 'code');
            $jobExecutionPids = $this->getEntityFieldAsArray('AkeneoBatchBundle:JobExecution', 'pid');
            $this->assertEquals($expectedJobData['job_instance_codes'], $jobInstanceCodes);
            $this->assertEquals(explode(',', $expectedJobData['job_execution_pids']), $jobExecutionPids);
        }
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help'                             => [
                '$expectedContent' => "Usage:\n  oro:cron:batch:cleanup [options]",
                '$params'          => ['--help']
            ],
            'should show no records found'                 => [
                '$expectedContent' => 'There are no jobs eligible for clean up',
                '$params'          => ['-i' => '1 year']
            ],
            'should show success output and records count' => [
                '$expectedContent' => "Batch jobs will be deleted: 6" . PHP_EOL . "Batch job history cleanup complete",
                '$params'          => ['-i' => '2 weeks']
            ]
        ];
    }

    /**
     * @param string $repositoryName
     * @param string $field
     *
     * @return array
     */
    protected function getEntityFieldAsArray($repositoryName, $field)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getRepository($repositoryName)
            ->createQueryBuilder('i');

        $items = $qb->select('i.' . $field)
            ->orderBy('i.' . $field)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            function ($item) use ($field) {
                return $item[$field];
            },
            $items
        );
    }
}
