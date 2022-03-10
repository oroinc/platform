<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorIterator;
use Oro\Bundle\ApiBundle\Processor\OptimizedProcessorIteratorFactory;
use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\Tests\Unit\NotDisabledApplicableChecker;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OptimizedProcessorIteratorTest extends \PHPUnit\Framework\TestCase
{
    private function getOptimizedProcessorIterator(
        array $processors,
        array $groups,
        Context $context,
        ApplicableCheckerInterface $applicableChecker = null
    ): OptimizedProcessorIterator {
        $chainApplicableChecker = new ChainApplicableChecker();
        if ($applicableChecker) {
            $chainApplicableChecker->addChecker($applicableChecker);
        }

        $factory = new OptimizedProcessorIteratorFactory();
        $processorBag = $this->createMock(ProcessorBagInterface::class);
        $factory->setProcessorBag($processorBag);
        $processorBag->expects(self::any())
            ->method('getActionGroups')
            ->with($context->getAction())
            ->willReturn($groups);

        return $factory->createProcessorIterator(
            $processors,
            $context,
            $chainApplicableChecker,
            $this->getProcessorRegistry()
        );
    }

    private function getContext(): Context
    {
        $context = new Context();
        $context->setAction('test');

        return $context;
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

    /**
     * @param array                      $expectedProcessors [processor id => group, ...]
     * @param OptimizedProcessorIterator $processors
     */
    private function assertProcessors(array $expectedProcessors, OptimizedProcessorIterator $processors)
    {
        $actualProcessors = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $actualProcessors[$processor->getProcessorId()] = $processors->getGroup();
            self::assertEquals($processor->getProcessorId(), $processors->getProcessorId());
        }

        self::assertEquals($expectedProcessors, $actualProcessors);
    }

    public function testNoApplicableRules()
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testNoGroupRelatedApplicableRules()
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1', 'disabled' => true]],
            ['processor4', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1'],
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor4' => null
            ],
            $iterator
        );
    }

    public function testSkipGroups()
    {
        $context = $this->getContext();
        $context->skipGroup('group1');
        $context->skipGroup('group3');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testSkipGroupsWithoutUngroupedProcessors()
    {
        $context = $this->getContext();
        $context->skipGroup('group1');
        $context->skipGroup('group3');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group3']],
            ['processor6', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor3' => 'group2',
                'processor4' => 'group2'
            ],
            $iterator
        );
    }

    public function testLastGroup()
    {
        $context = $this->getContext();
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => 'group1',
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testUnknownLastGroup()
    {
        $context = $this->getContext();
        $context->setLastGroup('unknown_group');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testLastGroupWithoutUngroupedProcessors()
    {
        $context = $this->getContext();
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group3']],
            ['processor6', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => 'group1',
                'processor2' => 'group1',
                'processor3' => 'group2',
                'processor4' => 'group2'
            ],
            $iterator
        );
    }

    public function testCombinationOfLastGroupAndSkipGroup()
    {
        $context = $this->getContext();
        $context->skipGroup('group1');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testLastGroupShouldBeSkipped()
    {
        $context = $this->getContext();
        $context->skipGroup('group1');
        $context->skipGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testFirstGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor6' => 'group3',
                'processor7' => 'group3',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testUnknownFirstGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('unknown_group');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupWithoutUngroupedProcessors()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group3']],
            ['processor6', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor3' => 'group2',
                'processor4' => 'group2',
                'processor5' => 'group3',
                'processor6' => 'group3'
            ],
            $iterator
        );
    }

    public function testFirstGroupEqualsToLastGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group3']],
            ['processor7', ['group' => 'group3']],
            ['processor8', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testAllProcessorsFromLastGroupAreNotApplicable()
    {
        $context = $this->getContext();
        $context->setLastGroup('group1');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1', 'disabled' => true]],
            ['processor3', ['group' => 'group1', 'disabled' => true]],
            ['processor4', ['group' => 'group2']],
            ['processor5', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstProcessorFromLastGroupAreNotApplicable()
    {
        $context = $this->getContext();
        $context->setLastGroup('group1');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1', 'disabled' => true]],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group2']],
            ['processor5', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group1',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testLastProcessorFromLastGroupAreNotApplicable()
    {
        $context = $this->getContext();
        $context->setLastGroup('group1');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1', 'disabled' => true]],
            ['processor4', ['group' => 'group2']],
            ['processor5', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group4');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group3']],
            ['processor5', ['group' => 'group4']],
            ['processor6', ['group' => 'group5']],
            ['processor7', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1', 'group2', 'group3', 'group4', 'group5'],
            $context
        );

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor7' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroupWithoutUngroupedProcessors()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group4');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group2']],
            ['processor3', ['group' => 'group3']],
            ['processor4', ['group' => 'group4']],
            ['processor5', ['group' => 'group5']]
        ];

        $iterator = $this->getOptimizedProcessorIterator(
            $processors,
            ['group1', 'group2', 'group3', 'group4', 'group5'],
            $context
        );

        $this->assertProcessors(
            [],
            $iterator
        );
    }

    public function testFirstGroupEqualsLastGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group3']],
            ['processor5', []]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group2',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutUngroupedProcessors()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group2']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            ['processor2' => 'group2'],
            $iterator
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutProcessorsInThisGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            [],
            $iterator
        );
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInFirstGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group3');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            ['processor3' => 'group3'],
            $iterator
        );
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInLastGroup()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getOptimizedProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        $this->assertProcessors(
            ['processor1' => 'group1'],
            $iterator
        );
    }
}
