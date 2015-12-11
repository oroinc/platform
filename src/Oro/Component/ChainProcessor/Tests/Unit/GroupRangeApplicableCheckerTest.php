<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;

class GroupRangeApplicableCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupRangeApplicableCheckerWithoutFirstAndLastGroups()
    {
        $context = new Context();
        $context->setAction('action1');

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');

        $this->assertProcessors(
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

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');

        $this->assertProcessors(
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

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');

        $this->assertProcessors(
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

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);
        $processorBag->addGroup('group2', 'action1', -20);
        $processorBag->addGroup('group4', 'action1', -30);
        $processorBag->addGroup('group3', 'action1', -40);
        $processorBag->addGroup('group5', 'action1', -50);
        $processorBag->addGroup('group6', 'action1', -60);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');
        $processorBag->addProcessor('processor2', [], 'action1', 'group2');
        $processorBag->addProcessor('processor3', [], 'action1', 'group3');
        $processorBag->addProcessor('processor4', [], 'action1', 'group4');
        $processorBag->addProcessor('processor5', [], 'action1', 'group5');
        $processorBag->addProcessor('processor6', [], 'action1', 'group6');

        $this->assertProcessors(
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

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);
        $processorBag->addGroup('group2', 'action1', -20);
        $processorBag->addGroup('group4', 'action1', -30);
        $processorBag->addGroup('group3', 'action1', -40);
        $processorBag->addGroup('group5', 'action1', -50);
        $processorBag->addGroup('group6', 'action1', -60);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');
        $processorBag->addProcessor('processor2', [], 'action1', 'group2');
        $processorBag->addProcessor('processor3', [], 'action1', 'group3');
        $processorBag->addProcessor('processor4', [], 'action1', 'group4');
        $processorBag->addProcessor('processor5', [], 'action1', 'group5');
        $processorBag->addProcessor('processor6', [], 'action1', 'group6');

        $this->assertProcessors(
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

        $processorBag = new ProcessorBag($this->getProcessorFactory());

        $processorBag->addGroup('group1', 'action1', -10);
        $processorBag->addGroup('group2', 'action1', -20);
        $processorBag->addGroup('group4', 'action1', -30);
        $processorBag->addGroup('group3', 'action1', -40);
        $processorBag->addGroup('group5', 'action1', -50);
        $processorBag->addGroup('group6', 'action1', -60);

        $processorBag->addProcessor('processor1_no_action', []);
        $processorBag->addProcessor('processor2_no_action', [], null, null, -65536);
        $processorBag->addProcessor('processor1_no_group', [], 'action1');
        $processorBag->addProcessor('processor2_no_group', [], 'action1', null, -65280);
        $processorBag->addProcessor('processor1', [], 'action1', 'group1');
        $processorBag->addProcessor('processor2', [], 'action1', 'group2');
        $processorBag->addProcessor('processor3', [], 'action1', 'group3');
        $processorBag->addProcessor('processor4', [], 'action1', 'group4');
        $processorBag->addProcessor('processor5', [], 'action1', 'group5');
        $processorBag->addProcessor('processor6', [], 'action1', 'group6');

        $this->assertProcessors(
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

    /**
     * @return ProcessorFactoryInterface
     */
    protected function getProcessorFactory()
    {
        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
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
    protected function assertProcessors(array $expectedProcessorIds, \Iterator $processors)
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        $this->assertEquals($expectedProcessorIds, $processorIds);
    }
}
