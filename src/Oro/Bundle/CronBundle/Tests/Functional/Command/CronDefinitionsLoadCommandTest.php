<?php

namespace Oro\Bundle\CronBundle\Tests\Functinal\Command;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Tests\Functional\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\CronBundle\Tests\Functional\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\CronBundle\Tests\Functional\Fixtures\Bundles\TestBundle3\TestBundle3;
use Oro\Bundle\CronBundle\Tests\Functional\Command\DataFixtures\LoadScheduleData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CronDefinitionsLoadCommandTest extends WebTestCase
{
    /** @var ScheduleManager */
    protected $manager;

    protected $oldKernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadScheduleData::class]);
        $this->manager = $this->getContainer()->get('oro_cron.schedule_manager');

        if (null === $this->oldKernel) {
            $this->oldKernel = $this->getContainer()->get('kernel');
        } else {
            $this->getContainer()->set('kernel', $this->oldKernel);
        }
    }

    /**
     * @dataProvider cronCommandProvider
     *
     * @param array $bundles
     * @param array $expectedResult
     */
    public function testLoadCronDefinition(array $bundles, array $expectedResult)
    {
        $this->runApplicationCommand('oro:cron:definition:load', [], $bundles);

        foreach ($expectedResult as $test) {
            $this->assertSame(
                $test['result'],
                $this->manager->hasSchedule($test['command'], $test['arguments'], $test['definition'])
            );
        }
    }

    public function cronCommandProvider()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        $bundle3 = new TestBundle3();

        yield 'remove orphaned commands' => [
            'bundles' => [$bundle2],
            'expectedResult' => [
                [
                    'result' => false,
                    'command' => 'oro:test:not-exist-command',
                    'arguments' => [],
                    'definition' => '*/1 * * * *'
                ],
                [
                    'result' => true,
                    'command' => 'oro:test:exist-command',
                    'arguments' => ['--dry-run'],
                    'definition' => '* */5 * * *'
                ],
            ]
        ];

        yield 'load new cron commands' => [
            'bundles' => [$bundle1, $bundle2],
            'expectedResult' => [
                [
                    'result' => false,
                    'command' => 'oro:non-cron:test-b',
                    'arguments' => [],
                    'definition' => '*/5 * * * *'
                ],
                [
                    'result' => true,
                    'command' => 'oro:cron:test-a',
                    'arguments' => [],
                    'definition' => '*/5 * * * *'
                ],
                [
                    'result' => true,
                    'command' => 'oro:cron:test-c',
                    'arguments' => [],
                    'definition' => '*/5 * * * *'
                ],
            ]
        ];

        yield 'update exist cron commands' => [
            'bundles' => [$bundle2, $bundle3],
            'expectedResult' => [
                [
                    'result' => true,
                    'command' => 'oro:cron:test-c',
                    'arguments' => [],
                    'definition' => '*/6 * * * *'
                ],
                [
                    'result' => false,
                    'command' => 'oro:cron:test-c',
                    'arguments' => [],
                    'definition' => '*/5 * * * *'
                ],
            ]
        ];
    }

    /**
     * @param string $name
     * @param array $params
     * @param BundleInterface[] $additionalBundles
     * @return string
     */
    protected function runApplicationCommand($name, array $params = [], array $additionalBundles = [])
    {
        /** @var KernelInterface $kernel */
        $kernel = clone $this->getContainer()->get('kernel');
        $kernel->boot();

        $bundles = [];
        foreach ($additionalBundles as $bundle) {
            $bundle->setContainer($kernel->getContainer());
            $bundles[$bundle->getName()] = $bundle;
        }

        $reflect = new \ReflectionObject($kernel);
        $property = $reflect->getProperty('bundles');
        $property->setAccessible(true);
        $property->setValue($kernel, array_merge($kernel->getBundles(), $bundles));
        $this->getContainer()->set('kernel', $kernel);

        return $this->runCommand($name, $params);
    }
}
