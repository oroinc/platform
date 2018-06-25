<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;

class GroupRangeApplicableCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testGroupRangeApplicableCheckerWithoutFirstAndLastGroups()
    {
        $context = new Context();
        $context->setAction('action1');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testGroupRangeApplicableCheckerWithUnknownFirstGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('unknown_group');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testGroupRangeApplicableCheckerWithUnknownLastGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setLastGroup('unknown_group');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testGroupRangeApplicableCheckerWithFirstAndLastGroups()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group5');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testGroupRangeApplicableCheckerWithFirstGroupOnly()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testGroupRangeApplicableCheckerWithLastGroupOnly()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setLastGroup('group5');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testFirstGroupEqualsLastGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testFirstGroupEqualsLastGroupWithoutUngroupedProcessors()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testFirstGroupEqualsLastGroupWithoutProcessorsInThisGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testFirstGroupAndLastGroupWithoutProcessorsInFirstGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group2');
        $context->setLastGroup('group3');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    public function testFirstGroupAndLastGroupWithoutProcessorsInLastGroup()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');

        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $this->getProcessorFactory());

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

    /**
     * @return ProcessorFactoryInterface
     */
    protected function getProcessorFactory()
    {
        $factory = $this->createMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->any())
            ->method('getProcessor')
            ->willReturnCallback(
                function ($processorId) {
                    return new ProcessorMock($processorId);
                }
            );

        return $factory;
    }

    /**
     * @param string[]  $expectedProcessorIds
     * @param \Iterator $processors
     */
    protected static function assertProcessors(array $expectedProcessorIds, \Iterator $processors)
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        self::assertEquals($expectedProcessorIds, $processorIds);
    }
}
