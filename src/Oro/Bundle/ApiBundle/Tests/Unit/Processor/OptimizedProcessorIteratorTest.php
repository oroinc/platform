<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\Tests\Unit\NotDisabledApplicableChecker;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorIterator;

class OptimizedProcessorIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array                           $processors
     * @param Context                         $context
     * @param ApplicableCheckerInterface|null $applicableChecker
     *
     * @return OptimizedProcessorIterator
     */
    protected function getOptimizedProcessorIterator(
        array $processors,
        Context $context,
        ApplicableCheckerInterface $applicableChecker = null
    ) {
        $chainApplicableChecker = new ChainApplicableChecker();
        if ($applicableChecker) {
            $chainApplicableChecker->addChecker($applicableChecker);
        }

        return new OptimizedProcessorIterator(
            $processors,
            $context,
            $chainApplicableChecker,
            $this->getProcessorFactory()
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

    public function testNoApplicableRules()
    {
        $context = new Context();

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
            ],
            $iterator
        );
    }

    public function testNoGroupRelatedApplicableRules()
    {
        $context = new Context();

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1', 'disabled' => true]],
            ['processor' => 'processor4', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor4',
            ],
            $iterator
        );
    }

    public function testSkipGroups()
    {
        $context = new Context();
        $context->skipGroup('group1');
        $context->skipGroup('group3');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor4',
                'processor5',
                'processor8',
            ],
            $iterator
        );
    }

    public function testSkipGroupsWithoutUngroupedProcessors()
    {
        $context = new Context();
        $context->skipGroup('group1');
        $context->skipGroup('group3');

        $processors = [
            ['processor' => 'processor1', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor3',
                'processor4',
            ],
            $iterator
        );
    }

    public function testLastGroup()
    {
        $context = new Context();
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
                'processor4',
                'processor5',
                'processor8',
            ],
            $iterator
        );
    }

    public function testUnknownLastGroup()
    {
        $context = new Context();
        $context->setLastGroup('unknown_group');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
            ],
            $iterator
        );
    }

    public function testLastGroupWithoutUngroupedProcessors()
    {
        $context = new Context();
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
                'processor4',
            ],
            $iterator
        );
    }

    public function testCombinationOfLastGroupAndSkipGroup()
    {
        $context = new Context();
        $context->skipGroup('group1');
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor4',
                'processor5',
                'processor8',
            ],
            $iterator
        );
    }

    public function testLastGroupShouldBeSkipped()
    {
        $context = new Context();
        $context->skipGroup('group1');
        $context->skipGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor8',
            ],
            $iterator
        );
    }

    public function testFirstGroup()
    {
        $context = new Context();
        $context->setFirstGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor4',
                'processor5',
                'processor6',
                'processor7',
                'processor8',
            ],
            $iterator
        );
    }

    public function testUnknownFirstGroup()
    {
        $context = new Context();
        $context->setFirstGroup('unknown_group');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
            ],
            $iterator
        );
    }

    public function testFirstGroupWithoutUngroupedProcessors()
    {
        $context = new Context();
        $context->setFirstGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor3',
                'processor4',
                'processor5',
                'processor6',
            ],
            $iterator
        );
    }

    public function testFirstGroupEqualsToLastGroup()
    {
        $context = new Context();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor7', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor8', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor4',
                'processor5',
                'processor8',
            ],
            $iterator
        );
    }

    public function testAllProcessorsFromLastGroupAreNotApplicable()
    {
        $context = new Context();
        $context->setLastGroup('group1');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1', 'disabled' => true]],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1', 'disabled' => true]],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor5',
            ],
            $iterator
        );
    }

    public function testFirstProcessorFromLastGroupAreNotApplicable()
    {
        $context = new Context();
        $context->setLastGroup('group1');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1', 'disabled' => true]],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor3',
                'processor5',
            ],
            $iterator
        );
    }

    public function testLastProcessorFromLastGroupAreNotApplicable()
    {
        $context = new Context();
        $context->setLastGroup('group1');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group1', 'disabled' => true]],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor5', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor5',
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroup()
    {
        $context = new Context();
        $context->setFirstGroup('group4');
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group4']],
            ['processor' => 'processor6', 'attributes' => ['group' => 'group5']],
            ['processor' => 'processor7', 'attributes' => []],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [
                'processor1',
                'processor7',
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroupWithoutUngroupedProcessors()
    {
        $context = new Context();
        $context->setFirstGroup('group4');
        $context->setLastGroup('group2');

        $processors = [
            ['processor' => 'processor1', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group3']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group4']],
            ['processor' => 'processor5', 'attributes' => ['group' => 'group5']],
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, $context);

        $this->assertProcessors(
            [],
            $iterator
        );
    }
}
