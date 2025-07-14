<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Provider;

use Oro\Bundle\MessageQueueBundle\Provider\JobConfigurationProvider;
use PHPUnit\Framework\TestCase;

class JobConfigurationProviderTest extends TestCase
{
    public function testTimeBeforeStaleIsTakenFromDefaultIfNoneJobNameMatch(): void
    {
        $provider = new JobConfigurationProvider();
        $provider->setConfiguration(['default' => 1, 'jobs' => ['job1' => 2]]);
        $this->assertEquals(1, $provider->getTimeBeforeStaleForJobName('job2'));
    }

    public function testTimeBeforeStaleIsNullWhenConfigurationDoesNotHaveAnyDefaultValue(): void
    {
        $provider = new JobConfigurationProvider();
        $this->assertNull($provider->getTimeBeforeStaleForJobName('job'));
    }

    /**
     * @dataProvider dataProviderForJobNames
     */
    public function testTimeBeforeStaleForJobName(string $jobName, array $configuration, int $expectedTime): void
    {
        $provider = new JobConfigurationProvider();
        $provider->setConfiguration($configuration);
        $this->assertEquals($expectedTime, $provider->getTimeBeforeStaleForJobName($jobName));
    }

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
