<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GroupRangeApplicableCheckerTest extends TestCase
{
    public function testGroupRangeApplicableCheckerWithoutFirstAndLastGroups(): void
    {
        $context = new Context();
        $context->setAction('action1');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor1',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testGroupRangeApplicableCheckerWithUnknownFirstGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('unknown_group');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor1',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testGroupRangeApplicableCheckerWithUnknownLastGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setLastGroup('unknown_group');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor1',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testGroupRangeApplicableCheckerWithFirstAndLastGroups(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group5');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group4', 'action1', -30);
        $builder->addGroup('group3', 'action1', -40);
        $builder->addGroup('group5', 'action1', -50);
        $builder->addGroup('group6', 'action1', -60);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor2', [], 'action1', 'group2');
        $builder->addProcessor('processor3', [], 'action1', 'group3');
        $builder->addProcessor('processor4', [], 'action1', 'group4');
        $builder->addProcessor('processor5', [], 'action1', 'group5');
        $builder->addProcessor('processor6', [], 'action1', 'group6');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor2',
                'processor4',
                'processor3',
                'processor5',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testGroupRangeApplicableCheckerWithFirstGroupOnly(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group4', 'action1', -30);
        $builder->addGroup('group3', 'action1', -40);
        $builder->addGroup('group5', 'action1', -50);
        $builder->addGroup('group6', 'action1', -60);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor2', [], 'action1', 'group2');
        $builder->addProcessor('processor3', [], 'action1', 'group3');
        $builder->addProcessor('processor4', [], 'action1', 'group4');
        $builder->addProcessor('processor5', [], 'action1', 'group5');
        $builder->addProcessor('processor6', [], 'action1', 'group6');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor2',
                'processor4',
                'processor3',
                'processor5',
                'processor6',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testGroupRangeApplicableCheckerWithLastGroupOnly(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setLastGroup('group5');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group4', 'action1', -30);
        $builder->addGroup('group3', 'action1', -40);
        $builder->addGroup('group5', 'action1', -50);
        $builder->addGroup('group6', 'action1', -60);

        $builder->addProcessor('processor1_no_action', []);
        $builder->addProcessor('processor2_no_action', [], null, null, -1);
        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor2_no_group', [], 'action1', null, -1);
        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor2', [], 'action1', 'group2');
        $builder->addProcessor('processor3', [], 'action1', 'group3');
        $builder->addProcessor('processor4', [], 'action1', 'group4');
        $builder->addProcessor('processor5', [], 'action1', 'group5');
        $builder->addProcessor('processor6', [], 'action1', 'group6');

        self::assertProcessors(
            [
                'processor1_no_action',
                'processor1_no_group',
                'processor1',
                'processor2',
                'processor4',
                'processor3',
                'processor5',
                'processor2_no_group',
                'processor2_no_action',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testFirstGroupEqualsLastGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group3', 'action1', -30);

        $builder->addProcessor('processor1_no_group', [], 'action1');
        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor2', [], 'action1', 'group2');
        $builder->addProcessor('processor3', [], 'action1', 'group3');
        $builder->addProcessor('processor3_no_group', [], 'action1', null, -255);

        self::assertProcessors(
            [
                'processor1_no_group',
                'processor2',
                'processor3_no_group',
            ],
            $processorBag->getProcessors($context)
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutUngroupedProcessors(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group3', 'action1', -30);

        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor2', [], 'action1', 'group2');
        $builder->addProcessor('processor3', [], 'action1', 'group3');

        self::assertProcessors(
            ['processor2'],
            $processorBag->getProcessors($context)
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutProcessorsInThisGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group3', 'action1', -30);

        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor3', [], 'action1', 'group3');

        self::assertProcessors(
            [],
            $processorBag->getProcessors($context)
        );
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInFirstGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group3');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group3', 'action1', -30);

        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor3', [], 'action1', 'group3');

        self::assertProcessors(
            ['processor3'],
            $processorBag->getProcessors($context)
        );
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInLastGroup(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorRegistry());

        $builder->addGroup('group1', 'action1', -10);
        $builder->addGroup('group2', 'action1', -20);
        $builder->addGroup('group3', 'action1', -30);

        $builder->addProcessor('processor1', [], 'action1', 'group1');
        $builder->addProcessor('processor3', [], 'action1', 'group3');

        self::assertProcessors(
            ['processor1'],
            $processorBag->getProcessors($context)
        );
    }

    private function getProcessorRegistry(): ProcessorRegistryInterface
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnCallback(function ($processorId) {
                return new ProcessorMock($processorId);
            });

        return $processorRegistry;
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
