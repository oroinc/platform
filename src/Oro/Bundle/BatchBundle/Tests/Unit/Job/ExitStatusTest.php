<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Job;

use Oro\Bundle\BatchBundle\Job\ExitStatus;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ExitStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testSetExitCode(): void
    {
        $status = new ExitStatus(ExitStatus::COMPLETED);
        $status->setExitCode(ExitStatus::STOPPED);
        self::assertEquals(ExitStatus::STOPPED, $status->getExitCode());
    }

    public function testExitStatusNullDescription(): void
    {
        $status = new ExitStatus('10');
        self::assertEquals('', $status->getExitDescription());
    }

    public function testExitStatusBooleanInt(): void
    {
        $status = new ExitStatus('10');
        self::assertEquals('10', $status->getExitCode());
    }

    public function testExitStatusConstantsContinuable(): void
    {
        $status = new ExitStatus(ExitStatus::EXECUTING);
        self::assertEquals('EXECUTING', $status->getExitCode());
    }

    public function testExitStatusConstantsFinished(): void
    {
        $status = new ExitStatus(ExitStatus::COMPLETED);
        self::assertEquals('COMPLETED', $status->getExitCode());
    }

    public function testEqualsWithSameProperties(): void
    {
        $executing = new ExitStatus(ExitStatus::EXECUTING);
        self::assertEquals($executing, new ExitStatus('EXECUTING'));
    }

    public function testEqualsSelf(): void
    {
        $status = new ExitStatus('test');
        self::assertEquals($status, $status);
    }

    public function testEquals(): void
    {
        self::assertEquals(new ExitStatus('test'), new ExitStatus('test'));
    }

    public function testEqualsWithNull(): void
    {
        $executing = new ExitStatus(ExitStatus::EXECUTING);
        self::assertNotEquals(null, $executing);
    }

    public function testAndExitStatusStillExecutable(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);
        $executing3 = new ExitStatus(ExitStatus::EXECUTING);

        self::assertEquals(
            $executing1->getExitCode(),
            $executing2->logicalAnd($executing3)->getExitCode()
        );
    }

    public function testAndExitStatusWhenFinishedAddedToContinuable(): void
    {
        $completed1 = new ExitStatus(ExitStatus::COMPLETED);
        $executing = new ExitStatus(ExitStatus::EXECUTING);
        $completed2 = new ExitStatus(ExitStatus::COMPLETED);

        self::assertEquals(
            $completed1->getExitCode(),
            $executing->logicalAnd($completed2)->getExitCode()
        );
    }

    public function testAndExitStatusWhenContinuableAddedToFinished(): void
    {
        $completed1 = new ExitStatus(ExitStatus::COMPLETED);
        $executing = new ExitStatus(ExitStatus::EXECUTING);
        $completed2 = new ExitStatus(ExitStatus::COMPLETED);

        self::assertEquals(
            $completed1->getExitCode(),
            $completed2->logicalAnd($executing)->getExitCode()
        );
    }

    public function testAndExitStatusWhenCustomContinuableAddedToContinuable(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        self::assertEquals(
            'CUSTOM',
            $executing1->logicalAnd(
                $executing2->setExitCode('CUSTOM')
            )->getExitCode()
        );
    }

    public function testAndExitStatusWhenCustomCompletedAddedToCompleted(): void
    {
        $completed = new ExitStatus(ExitStatus::COMPLETED);
        $executing = new ExitStatus(ExitStatus::EXECUTING);

        self::assertEquals(
            'COMPLETED_CUSTOM',
            $completed->logicalAnd(
                $executing->setExitCode('COMPLETED_CUSTOM')
            )->getExitCode()
        );
    }

    public function testAndExitStatusFailedPlusFinished(): void
    {
        $completed1 = new ExitStatus(ExitStatus::COMPLETED);
        $failed1 = new ExitStatus(ExitStatus::FAILED);

        $completed2 = new ExitStatus(ExitStatus::COMPLETED);
        $failed2 = new ExitStatus(ExitStatus::FAILED);

        self::assertEquals('FAILED', $completed1->logicalAnd($failed1)->getExitCode());
        self::assertEquals('FAILED', $failed2->logicalAnd($completed2)->getExitCode());
    }

    public function testAndExitStatusWhenCustomContinuableAddedToFinished(): void
    {
        $completed = new ExitStatus(ExitStatus::COMPLETED);
        $executing = new ExitStatus(ExitStatus::EXECUTING);

        self::assertEquals(
            'CUSTOM',
            $completed->logicalAnd($executing->setExitCode('CUSTOM'))->getExitCode()
        );
    }

    public function testAddExitCode(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->setExitCode('FOO');

        self::assertNotSame($executing2, $status);
        self::assertEquals('FOO', $status->getExitCode());
    }

    public function testAddExitCodeToExistingStatus(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->setExitCode('FOO')->setExitCode('BAR');

        self::assertNotSame($executing2, $status);
        self::assertEquals('BAR', $status->getExitCode());
    }

    public function testAddExitCodeToSameStatus(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);
        $executing3 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->setExitCode($executing2->getExitCode());
        self::assertNotSame($executing3, $status);
        self::assertEquals($executing3->getExitCode(), $status->getExitCode());
    }

    public function testAddExitDescription(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->addExitDescription('Foo');

        self::assertNotSame($executing2, $status);
        self::assertEquals('Foo', $status->getExitDescription());
    }

    public function testAddExitDescriptionWithStacktrace(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->addExitDescription(new \Exception('Foo'));
        self::assertNotSame($executing2, $status);
        $description = $status->getExitDescription();
        self::assertNotSame(
            strstr($description, 'Foo'),
            -1,
            'Wrong description: ' . $description
        );
        self::assertNotSame(
            strstr($description, 'Exception'),
            -1,
            'Wrong description: ' . $description
        );
    }

    public function testAddExitDescriptionToSameStatus(): void
    {
        $executing1 = new ExitStatus(ExitStatus::EXECUTING);
        $executing2 = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing1->addExitDescription('Foo')->addExitDescription('Foo');
        self::assertNotSame($executing2, $status);
        self::assertEquals('Foo', $status->getExitDescription());
    }

    public function testAddEmptyExitDescription(): void
    {
        $executing = new ExitStatus(ExitStatus::EXECUTING);

        $status = $executing->addExitDescription('Foo')->addExitDescription('');
        self::assertEquals('Foo', $status->getExitDescription());
    }

    public function testAddExitCodeWithDescription(): void
    {
        $bar = new ExitStatus('BAR', 'Bar');
        $status = $bar->setExitCode('FOO');

        self::assertEquals('FOO', $status->getExitCode());
        self::assertEquals('Bar', $status->getExitDescription());
    }

    public function testAddExitDescriptionToExistingDescription(): void
    {
        $status = new ExitStatus(ExitStatus::EXECUTING);

        $status->addExitDescription('Foo');
        $status->addExitDescription('Bar');

        self::assertEquals('Foo;Bar', $status->getExitDescription());
    }

    public function testUnknownIsRunning(): void
    {
        $unknown = new ExitStatus(ExitStatus::UNKNOWN);
        self::assertTrue($unknown->isRunning());
    }

    public function testToString(): void
    {
        $status = new ExitStatus(ExitStatus::COMPLETED, 'My test description for completed status');

        self::assertEquals('[COMPLETED] My test description for completed status', (string)$status);
    }
}
