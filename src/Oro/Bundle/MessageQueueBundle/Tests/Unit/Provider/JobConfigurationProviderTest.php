<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Provider;

use Oro\Bundle\MessageQueueBundle\Provider\JobConfigurationProvider;

class JobConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testTimeBeforeStaleIsTakenFromDefaultIfNoneJobNameMatch()
    {
        $provider = new JobConfigurationProvider();
        $provider->setConfiguration(['default' => 1, 'jobs' => ['job1' => 2]]);
        $this->assertEquals(1, $provider->getTimeBeforeStaleForJobName('job2'));
    }

    public function testTimeBeforeStaleIsNullWhenConfigurationDoesNotHaveAnyDefaultValue()
    {
        $provider = new JobConfigurationProvider();
        $this->assertNull($provider->getTimeBeforeStaleForJobName('job'));
    }

    /**
     * @dataProvider dataProviderForJobNames
     * @param string $jobName
     * @param array  $configuration
     * @param int    $expectedTime
     */
    public function testTimeBeforeStaleForJobName(string $jobName, array $configuration, int $expectedTime)
    {
        $provider = new JobConfigurationProvider();
        $provider->setConfiguration($configuration);
        $this->assertEquals($expectedTime, $provider->getTimeBeforeStaleForJobName($jobName));
    }

    /**
     * @return array
     */
    public function dataProviderForJobNames(): array
    {
        return [
            ['root.child1.child2.child3', ['jobs' => ['root.child1.child2.child3' => 3]], 3],
            ['root.child1.child2.child3', ['jobs' => ['root.child1.child2' => 2]], 2],
            ['root.child1.child2.child3', ['jobs' => ['root.child1' => 1]], 1],
            ['root.child1.child2.child3', ['jobs' => ['root' => 100]], 100],
            ['root.child1.child2.child3', ['jobs' => ['root' => 100, 'root.child1.child2' => 2]], 2],
            'job with colon delimiter' => [
                'jobName' => 'root:child1:child2:child3',
                'configuration' => ['jobs' => ['root' => 100, 'root:child1:child2' => 5]],
                'expectedTime' => 5
            ],
        ];
    }
}
