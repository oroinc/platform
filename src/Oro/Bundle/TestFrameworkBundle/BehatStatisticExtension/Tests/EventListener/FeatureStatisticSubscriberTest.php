<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Teardown;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener\FeatureStatisticSubscriber;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Symfony\Component\Console\Output\OutputInterface;

class FeatureStatisticSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureStatisticManager|\PHPUnit\Framework\MockObject\MockObject */
    private $statisticManager;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $output;

    /** @var FeatureStatisticSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->statisticManager = $this->createMock(FeatureStatisticManager::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->subscriber = new FeatureStatisticSubscriber($this->statisticManager);
        $this->subscriber->setOutput($this->output);
    }

    public function testListenEventWithSkip(): void
    {
        /** @var Environment $env */
        $env = $this->createMock(Environment::class);
        /** @var FeatureNode $feature */
        $feature = $this->createMock(FeatureNode::class);

        /** @var TestResult|\PHPUnit\Framework\MockObject\MockObject $result */
        $result = $this->createMock(TestResult::class);
        $result->expects($this->any())
            ->method('isPassed')
            ->willReturn(true);

        /** @var Teardown $teardown */
        $teardown = $this->createMock(Teardown::class);

        $beforeFeatureTestedEvent = new BeforeFeatureTested($env, $feature);
        $afterFeatureTestedEvent = new AfterFeatureTested($env, $feature, $result, $teardown);
        $afterExerciseCompletedEvent = new AfterExerciseCompleted([], $result, $teardown);

        $this->statisticManager->expects($this->never())
            ->method($this->anything());

        /** @var Formatter $formatter */
        $formatter = $this->createMock(Formatter::class);

        $this->subscriber->setSkip(true);
        $this->subscriber->listenEvent($formatter, $beforeFeatureTestedEvent, 'test');
        $this->subscriber->listenEvent($formatter, $afterFeatureTestedEvent, 'test');
        $this->subscriber->listenEvent($formatter, $afterExerciseCompletedEvent, 'test');
    }

    public function testListenEventOnBeforeFeatureTested(): void
    {
        /** @var Environment $env */
        $env = $this->createMock(Environment::class);
        /** @var FeatureNode $feature */
        $feature = $this->createMock(FeatureNode::class);
        /** @var TestResult|\PHPUnit\Framework\MockObject\MockObject $result */
        $result = $this->createMock(TestResult::class);
        $result->expects($this->once())
            ->method('isPassed')
            ->willReturn(true);

        /** @var Teardown $teardown */
        $teardown = $this->createMock(Teardown::class);

        $beforeFeatureTestedEvent = new BeforeFeatureTested($env, $feature);
        $afterFeatureTestedEvent = new AfterFeatureTested($env, $feature, $result, $teardown);
        $afterExerciseCompletedEvent = new AfterExerciseCompleted([], $result, $teardown);

        $this->statisticManager->expects($this->once())
            ->method('addStatistic')
            ->with($feature, $this->isType('float'));

        $this->statisticManager->expects($this->exactly(2))
            ->method('saveStatistics');

        /** @var Formatter $formatter */
        $formatter = $this->createMock(Formatter::class);

        $this->subscriber->listenEvent($formatter, $beforeFeatureTestedEvent, 'test');
        $this->subscriber->listenEvent($formatter, $afterFeatureTestedEvent, 'test');
        $this->subscriber->listenEvent($formatter, $afterExerciseCompletedEvent, 'test');
    }

    public function testCaptureStats(): void
    {
        /** @var Environment $env */
        $env = $this->createMock(Environment::class);
        /** @var FeatureNode $feature */
        $feature = $this->createMock(FeatureNode::class);
        /** @var TestResult|\PHPUnit\Framework\MockObject\MockObject $result */
        $result = $this->createMock(TestResult::class);
        $result->expects($this->once())
            ->method('isPassed')
            ->willReturn(true);

        /** @var Teardown $teardown */
        $teardown = $this->createMock(Teardown::class);

        $this->statisticManager->expects($this->once())
            ->method('addStatistic')
            ->with($feature, $this->isType('float'));

        $this->subscriber->record();
        $this->subscriber->captureStats(new AfterFeatureTested($env, $feature, $result, $teardown));
    }

    public function testCaptureStatsNotPassed(): void
    {
        /** @var Environment $env */
        $env = $this->createMock(Environment::class);
        /** @var FeatureNode $feature */
        $feature = $this->createMock(FeatureNode::class);
        /** @var TestResult|\PHPUnit\Framework\MockObject\MockObject $result */
        $result = $this->createMock(TestResult::class);
        $result->expects($this->once())
            ->method('isPassed')
            ->willReturn(false);

        /** @var Teardown $teardown */
        $teardown = $this->createMock(Teardown::class);

        $this->statisticManager->expects($this->never())
            ->method('addStatistic');

        $this->subscriber->record();
        $this->subscriber->captureStats(new AfterFeatureTested($env, $feature, $result, $teardown));
    }

    public function testSaveStats(): void
    {
        $this->statisticManager->expects($this->once())
            ->method('saveStatistics');

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('<info>Statistics was recorded successfully.</info>');

        $this->subscriber->saveStats();
    }

    public function testSaveStatsException(): void
    {
        $this->statisticManager->expects($this->once())
            ->method('saveStatistics')
            ->willThrowException(new \Exception('test'));

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(sprintf('<error>Exception while record the statistics:%stest</error>', PHP_EOL));

        $this->subscriber->saveStats();
    }
}
