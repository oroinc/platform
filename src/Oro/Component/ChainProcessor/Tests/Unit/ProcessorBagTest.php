<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProcessorBagTest extends TestCase
{
    private ProcessorBagConfigBuilder $builder;
    private ProcessorBag $processorBag;

    #[\Override]
    protected function setUp(): void
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnCallback(function ($processorId) {
                return new ProcessorMock($processorId);
            });

        $this->builder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->builder, $processorRegistry);
    }

    public function testEmptyBag(): void
    {
        $context = new Context();
        $context->setAction('action1');

        self::assertProcessors(
            [],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testActions(): void
    {
        $this->builder->addProcessor('processor1', [], 'action1');
        $this->builder->addProcessor('processor2', [], 'action2');

        self::assertSame(
            ['action1', 'action2'],
            $this->processorBag->getActions()
        );
    }

    public function testActionGroupsForUnknownAction(): void
    {
        $this->builder->addGroup('group1', 'action1');

        $this->builder->addProcessor('processor1', [], 'action1', 'group1');

        self::assertSame(
            [],
            $this->processorBag->getActionGroups('unknown_action')
        );
    }

    public function testActionGroupsForActionWithoutGroups(): void
    {
        $this->builder->addProcessor('processor1', [], 'action1');
        $this->builder->addProcessor('processor2', [], 'action1');

        self::assertSame(
            [],
            $this->processorBag->getActionGroups('action1')
        );
    }

    public function testActionGroups(): void
    {
        $this->builder->addGroup('group3', 'action1', -10);
        $this->builder->addGroup('group2', 'action1', -20);
        $this->builder->addGroup('group1', 'action1', -30);

        $this->builder->addProcessor('processor1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor2', [], 'action1', 'group1');
        $this->builder->addProcessor('processor3', [], 'action1', 'group3');
        $this->builder->addProcessor('processor4', [], 'action1', 'group3');
        $this->builder->addProcessor('processor5', [], 'action1', 'group1');

        self::assertSame(
            ['group3', 'group2', 'group1'],
            $this->processorBag->getActionGroups('action1')
        );
    }

    public function testActionGroupsSorting(): void
    {
        $this->builder->addGroup('group3', 'action1', -30);
        $this->builder->addGroup('group2', 'action1', -20);
        $this->builder->addGroup('group1', 'action1', -10);

        $this->builder->addProcessor('processor1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor2', [], 'action1', 'group1');
        $this->builder->addProcessor('processor3', [], 'action1', 'group3');
        $this->builder->addProcessor('processor4', [], 'action1', 'group3');
        $this->builder->addProcessor('processor5', [], 'action1', 'group1');

        self::assertSame(
            ['group1', 'group2', 'group3'],
            $this->processorBag->getActionGroups('action1')
        );
    }

    public function testActionGroupsWithIdenticalPriority(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The priority 1 cannot be used for the group "group3" because the group with this priority already exists.'
            . ' Existing group: "group1". Action: "action1".'
        );

        $this->builder->addGroup('group1', 'action1', 1);
        $this->builder->addGroup('group2', 'action2', 1);
        $this->builder->addGroup('group3', 'action1', 1);
    }

    public function testBagWithoutGroups(): void
    {
        $context = new Context();

        $this->builder->addProcessor('processor1_1', [], 'action1');
        $this->builder->addProcessor('processor1_2', [], 'action1', null, -1);
        $this->builder->addProcessor('processor1_3', [], 'action1', null, 1);

        $this->builder->addProcessor('processor2_1', [], 'action2');
        $this->builder->addProcessor('processor2_2', [], 'action2', null, 1);
        $this->builder->addProcessor('processor2_3', [], 'action2', null, -1);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor1_3',
                'processor1_1',
                'processor1_2',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('action2');
        self::assertProcessors(
            [
                'processor2_2',
                'processor2_1',
                'processor2_3',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('unknown_action');
        self::assertProcessors(
            [],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWithGroups(): void
    {
        $context = new Context();

        $this->builder->addProcessor('processor1_1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor1_2', [], 'action1', 'group1', -1);
        $this->builder->addProcessor('processor1_3', [], 'action1', 'group1', 1);
        $this->builder->addProcessor('processor1_4', [], 'action1', 'group2');
        $this->builder->addProcessor('processor1_5', [], 'action1', 'group2', -1);
        $this->builder->addProcessor('processor1_6', [], 'action1', 'group2', 1);

        $this->builder->addProcessor('processor2_1', [], 'action2', 'group1');
        $this->builder->addProcessor('processor2_2', [], 'action2', 'group1', 1);
        $this->builder->addProcessor('processor2_3', [], 'action2', 'group1', -1);
        $this->builder->addProcessor('processor2_4', [], 'action2', 'group2');
        $this->builder->addProcessor('processor2_5', [], 'action2', 'group2', 1);
        $this->builder->addProcessor('processor2_6', [], 'action2', 'group2', -1);

        $this->builder->addGroup('group1', 'action1');
        $this->builder->addGroup('group2', 'action1', 1);

        $this->builder->addGroup('group1', 'action2');
        $this->builder->addGroup('group2', 'action2', -1);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor1_6',
                'processor1_4',
                'processor1_5',
                'processor1_3',
                'processor1_1',
                'processor1_2',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('action2');
        self::assertProcessors(
            [
                'processor2_2',
                'processor2_1',
                'processor2_3',
                'processor2_5',
                'processor2_4',
                'processor2_6',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('unknown_action');
        self::assertProcessors(
            [],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWhenBothGroupedAndUngroupedProcessorsExist(): void
    {
        $context = new Context();

        $this->builder->addGroup('group1', 'action1');
        $this->builder->addGroup('group2', 'action1', 1);

        $this->builder->addProcessor('processor1_1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor1_2', [], 'action1', 'group1', -1);
        $this->builder->addProcessor('processor1_3', [], 'action1', 'group1', 1);
        $this->builder->addProcessor('processor1_4', [], 'action1', 'group2');
        $this->builder->addProcessor('processor1_5', [], 'action1', 'group2', -1);
        $this->builder->addProcessor('processor1_6', [], 'action1', 'group2', 1);

        $this->builder->addProcessor('processor1_no_group', [], 'action1');
        $this->builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $this->builder->addProcessor('processor3_no_group', [], 'action1', null, 1);
        $this->builder->addProcessor('processor4_no_group', [], 'action1', null, -1000);
        $this->builder->addProcessor('processor5_no_group', [], 'action1', null, 1000);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor5_no_group',
                'processor3_no_group',
                'processor1_no_group',
                'processor1_6',
                'processor1_4',
                'processor1_5',
                'processor1_3',
                'processor1_1',
                'processor1_2',
                'processor2_no_group',
                'processor4_no_group',
            ],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWhenThereAreGroupedAndUngroupedAndCommonProcessors(): void
    {
        $context = new Context();

        $this->builder->addGroup('group1', 'action1');
        $this->builder->addGroup('group2', 'action1', 1);

        $this->builder->addGroup('group1', 'action2');

        $this->builder->addProcessor('processor1_1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor1_2', [], 'action1', 'group1', -1);
        $this->builder->addProcessor('processor1_3', [], 'action1', 'group1', 1);
        $this->builder->addProcessor('processor1_4', [], 'action1', 'group2');
        $this->builder->addProcessor('processor1_5', [], 'action1', 'group2', -1);
        $this->builder->addProcessor('processor1_6', [], 'action1', 'group2', 1);

        $this->builder->addProcessor('processor1_no_action', []);
        $this->builder->addProcessor('processor2_no_action', [], null, null, -1);
        $this->builder->addProcessor('processor3_no_action', [], null, null, 1);
        $this->builder->addProcessor('processor4_no_action', [], null, null, -1000);
        $this->builder->addProcessor('processor5_no_action', [], null, null, 1000);

        $this->builder->addProcessor('processor1_1_no_group', [], 'action1');
        $this->builder->addProcessor('processor1_2_no_group', [], 'action1', null, -1);
        $this->builder->addProcessor('processor1_3_no_group', [], 'action1', null, 1);
        $this->builder->addProcessor('processor1_4_no_group', [], 'action1', null, -1000);
        $this->builder->addProcessor('processor1_5_no_group', [], 'action1', null, 1000);

        $this->builder->addProcessor('processor2_1', [], 'action2', 'group1');
        $this->builder->addProcessor('processor2_1_no_group', [], 'action2');

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor5_no_action',
                'processor3_no_action',
                'processor1_no_action',
                'processor1_5_no_group',
                'processor1_3_no_group',
                'processor1_1_no_group',
                'processor1_6',
                'processor1_4',
                'processor1_5',
                'processor1_3',
                'processor1_1',
                'processor1_2',
                'processor1_2_no_group',
                'processor1_4_no_group',
                'processor2_no_action',
                'processor4_no_action',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('action2');
        self::assertProcessors(
            [
                'processor5_no_action',
                'processor3_no_action',
                'processor1_no_action',
                'processor2_1_no_group',
                'processor2_1',
                'processor2_no_action',
                'processor4_no_action',
            ],
            $this->processorBag->getProcessors($context)
        );

        $context->setAction('unknown_action');
        self::assertProcessors(
            [],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWhenThereAreStartingCommonProcessorsButNoEndingCommonProcessors(): void
    {
        $context = new Context();

        $this->builder->addGroup('group1', 'action1');

        $this->builder->addProcessor('processor1_1', [], 'action1', 'group1');

        $this->builder->addProcessor('processor1_no_action', []);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_1',
            ],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testBagWhenThereAreEndingCommonProcessorsButNoStartingCommonProcessors(): void
    {
        $context = new Context();

        $this->builder->addGroup('group1', 'action1');

        $this->builder->addProcessor('processor1_1', [], 'action1', 'group1');

        $this->builder->addProcessor('processor1_no_action', [], null, null, -1);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor1_1',
                'processor1_no_action',
            ],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testAddApplicableChecker(): void
    {
        $context = new Context();

        $this->processorBag->addApplicableChecker(new NotDisabledApplicableChecker());

        $this->builder->addProcessor('processor1_1', ['disabled' => true], 'action1');
        $this->builder->addProcessor('processor1_2', [], 'action1', null, -1);
        $this->builder->addProcessor('processor1_3', [], 'action1', null, 1);

        $context->setAction('action1');
        self::assertProcessors(
            [
                'processor1_3',
                'processor1_2',
            ],
            $this->processorBag->getProcessors($context)
        );
    }

    public function testUndefinedGroup(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The group "group2" is not defined. Processor: "processor2". Action: "action1".'
        );

        $context = new Context();

        $this->builder->addProcessor('processor1', [], 'action1', 'group1');
        $this->builder->addProcessor('processor2', [], 'action1', 'group2');
        $this->builder->addProcessor('processor3', [], 'action1', 'group3');

        $this->builder->addGroup('group1', 'action1');

        $context->setAction('action1');
        $this->processorBag->getProcessors($context);
    }

    public function testAddProcessorToFrozenBag(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The ProcessorBag is frozen.');

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);

        $this->builder->addProcessor('processor1', [], 'action1');
    }

    public function testAddGroupToFrozenBag(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The ProcessorBag is frozen.');

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);

        $this->builder->addGroup('group1', 'action1');
    }

    public function testAddApplicableCheckerToFrozenBag(): void
    {
        $context = new Context();
        $context->setAction('action1');

        $this->builder->addProcessor('processor1', ['disabled' => true], 'action1');
        $this->builder->addProcessor('processor2', [], 'action1');

        // freeze the processor bag
        // and make sure that the applicable checker is initialized and not blocks any processor
        self::assertCount(2, $this->processorBag->getProcessors($context));

        // add additional applicable checker that blocks disabled processors
        // and make sure that it is used from now
        $this->processorBag->addApplicableChecker(new NotDisabledApplicableChecker());
        self::assertCount(1, $this->processorBag->getProcessors($context));
    }

    public function testMaxGroupPriority(): void
    {
        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(
            'The value 256 is not valid priority of a group. It must be between -255 and 255.'
        );

        $this->builder->addGroup('group1', 'action1', 256);
        $this->builder->addProcessor('processor1', [], 'action1', 'group1');

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);
    }

    public function testMinGroupPriority(): void
    {
        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(
            'The value -256 is not valid priority of a group. It must be between -255 and 255.'
        );

        $this->builder->addGroup('group1', 'action1', -256);
        $this->builder->addProcessor('processor1', [], 'action1', 'group1');

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);
    }

    public function testMaxProcessorPriority(): void
    {
        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(
            'The value 256 is not valid priority of a processor. It must be between -255 and 255.'
        );

        $this->builder->addGroup('group1', 'action1');
        $this->builder->addProcessor('processor1', [], 'action1', 'group1', 256);

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);
    }

    public function testMinProcessorPriority(): void
    {
        $this->expectException(\RangeException::class);
        $this->expectExceptionMessage(
            'The value -256 is not valid priority of a processor. It must be between -255 and 255.'
        );

        $this->builder->addGroup('group1', 'action1');
        $this->builder->addProcessor('processor1', [], 'action1', 'group1', -256);

        $context = new Context();
        $context->setAction('action1');
        $this->processorBag->getProcessors($context);
    }

    public function testInternalPriorityForLastGroupedProcessor(): void
    {
        self::assertEquals(
            -130561,
            $this->callCalculatePriority(-255, -255)
        );
    }

    public function testInternalPriorityForFirstUngroupedProcessorExecutedAtEnd(): void
    {
        self::assertEquals(
            -130562,
            $this->callCalculatePriority(-1)
        );
    }

    public function testShouldNoLimitForPriorityOfUngroupedProcessorExecutedAtEnd(): void
    {
        self::assertEquals(
            -130561 - 1000,
            $this->callCalculatePriority(-1000)
        );
    }

    public function testInternalPriorityForFirstGroupedProcessor(): void
    {
        self::assertEquals(
            130559,
            $this->callCalculatePriority(255, 255)
        );
    }

    public function testInternalPriorityForLastUngroupedProcessorExecutedAtBegin(): void
    {
        self::assertEquals(
            130560,
            $this->callCalculatePriority(0)
        );
    }

    public function testShouldNoLimitForPriorityOfUngroupedProcessorExecutedAtBegin(): void
    {
        self::assertEquals(
            130560 + 1000,
            $this->callCalculatePriority(1000)
        );
    }

    public function testInternalPriorityForZeroProcessor(): void
    {
        self::assertEquals(
            -1,
            $this->callCalculatePriority(0, 0)
        );
    }

    public function testInternalPriorityForLastProcessorInZeroGroup(): void
    {
        self::assertEquals(
            -256,
            $this->callCalculatePriority(-255, 0)
        );
    }

    public function testInternalPriorityForFirstProcessorInZeroGroup(): void
    {
        self::assertEquals(
            254,
            $this->callCalculatePriority(255, 0)
        );
    }

    public function testProcessorPrioritiesShouldNotBeIntersected(): void
    {
        $prevMaxPriority = $this->callCalculatePriority(255, -255);
        $groupPriority = -254;
        while ($groupPriority <= 255) {
            $minPriority = $this->callCalculatePriority(-255, $groupPriority);
            self::assertSame(
                $prevMaxPriority,
                $minPriority - 1,
                sprintf(
                    'Failed expectation of the priority calculation.' . "\n"
                    . 'The calculated priority of the last processor from previous group is %s.' . "\n"
                    . 'The calculated priority of the first processor from current group is %s.' . "\n"
                    . 'The current group priority is %s.',
                    $prevMaxPriority,
                    $minPriority,
                    $groupPriority
                )
            );
            $prevMaxPriority = $this->callCalculatePriority(255, $groupPriority);
            $groupPriority++;
        }
    }

    private function callCalculatePriority(int $processorPriority, ?int $groupPriority = null): int
    {
        $method = new \ReflectionMethod($this->builder, 'calculatePriority');
        $method->setAccessible(true);

        return $method->invokeArgs(null, [$processorPriority, $groupPriority]);
    }

    private static function assertProcessors(array $expectedProcessorIds, \Iterator $processors): void
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        self::assertEquals($expectedProcessorIds, $processorIds);
    }
}
