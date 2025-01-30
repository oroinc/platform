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
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OptimizedProcessorIteratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test_action';

    private function getProcessorIterator(
        array $processors,
        array $groups,
        Context $context,
        ?ApplicableCheckerInterface $applicableChecker = null,
        bool $withApplicableCache = false
    ): OptimizedProcessorIterator {
        $processorBag = $this->createMock(ProcessorBagInterface::class);
        $processorBag->expects(self::any())
            ->method('getActionGroups')
            ->with($context->getAction())
            ->willReturn($groups);

        $factory = new OptimizedProcessorIteratorFactory($withApplicableCache ? [self::TEST_ACTION] : []);
        $factory->setProcessorBag($processorBag);

        return $factory->createProcessorIterator(
            $processors,
            $context,
            $applicableChecker ?? new ChainApplicableChecker(),
            $this->getProcessorRegistry()
        );
    }

    private function getContext(): Context
    {
        $context = new Context();
        $context->setAction(self::TEST_ACTION);

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
    private static function assertProcessors(array $expectedProcessors, OptimizedProcessorIterator $processors): void
    {
        $actualProcessors = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $actualProcessors[$processor->getProcessorId()] = $processors->getGroup();
            self::assertEquals($processor->getProcessorId(), $processors->getProcessorId());
        }

        self::assertSame($expectedProcessors, $actualProcessors);
    }

    public function testEmptyIterator(): void
    {
        $iterator = $this->getProcessorIterator([], [], $this->getContext());

        self::assertProcessors([], $iterator);
    }

    public function testIterator(): void
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['disabled' => true]],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group1', 'disabled' => true]],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group2', 'disabled' => true]]
        ];

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1'],
            $context,
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group1',
                'processor5' => 'group2'
            ],
            $iterator
        );
    }

    public function testIteratorWithApplicableCache(): void
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['disabled' => true]],
            ['processor3', ['group' => 'group1']],
            ['processor4', ['group' => 'group1', 'disabled' => true]],
            ['processor5', ['group' => 'group2']],
            ['processor6', ['group' => 'group2', 'disabled' => true]]
        ];

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1'],
            $context,
            new NotDisabledApplicableChecker(),
            true
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group1',
                'processor5' => 'group2'
            ],
            $iterator
        );
    }

    public function testNoApplicableRules(): void
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testNoGroupRelatedApplicableRules(): void
    {
        $context = $this->getContext();

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', ['group' => 'group1', 'disabled' => true]],
            ['processor4', []]
        ];

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1'],
            $context,
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor4' => null
            ],
            $iterator
        );
    }

    public function testSkipGroups(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testSkipGroupsWithoutUngroupedProcessors(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor3' => 'group2',
                'processor4' => 'group2'
            ],
            $iterator
        );
    }

    public function testLastGroup(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
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

    public function testUnknownLastGroup(): void
    {
        $context = $this->getContext();
        $context->setLastGroup('unknown_group');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testLastGroupWithoutUngroupedProcessors(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => 'group1',
                'processor2' => 'group1',
                'processor3' => 'group2',
                'processor4' => 'group2'
            ],
            $iterator
        );
    }

    public function testCombinationOfLastGroupAndSkipGroup(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testLastGroupShouldBeSkipped(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testFirstGroup(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
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

    public function testUnknownFirstGroup(): void
    {
        $context = $this->getContext();
        $context->setFirstGroup('unknown_group');

        $processors = [
            ['processor1', []],
            ['processor2', ['group' => 'group1']],
            ['processor3', []]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor3' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupWithoutUngroupedProcessors(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor3' => 'group2',
                'processor4' => 'group2',
                'processor5' => 'group3',
                'processor6' => 'group3'
            ],
            $iterator
        );
    }

    public function testFirstGroupEqualsToLastGroup(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor4' => 'group2',
                'processor5' => 'group2',
                'processor8' => null
            ],
            $iterator
        );
    }

    public function testAllProcessorsFromLastGroupAreNotApplicable(): void
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

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstProcessorFromLastGroupAreNotApplicable(): void
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

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group1',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testLastProcessorFromLastGroupAreNotApplicable(): void
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

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1', 'group2'],
            $context,
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor2' => 'group1',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroup(): void
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

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1', 'group2', 'group3', 'group4', 'group5'],
            $context
        );

        self::assertProcessors(
            [
                'processor1' => null,
                'processor7' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupAfterLastGroupWithoutUngroupedProcessors(): void
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

        $iterator = $this->getProcessorIterator(
            $processors,
            ['group1', 'group2', 'group3', 'group4', 'group5'],
            $context
        );

        self::assertProcessors([], $iterator);
    }

    public function testFirstGroupEqualsLastGroup(): void
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

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            [
                'processor1' => null,
                'processor3' => 'group2',
                'processor5' => null
            ],
            $iterator
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutUngroupedProcessors(): void
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group2']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            ['processor2' => 'group2'],
            $iterator
        );
    }

    public function testFirstGroupEqualsLastGroupWithoutProcessorsInThisGroup(): void
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors([], $iterator);
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInFirstGroup(): void
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group3');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            ['processor3' => 'group3'],
            $iterator
        );
    }

    public function testFirstGroupAndLastGroupWithoutProcessorsInLastGroup(): void
    {
        $context = $this->getContext();
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');

        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor3', ['group' => 'group3']]
        ];

        $iterator = $this->getProcessorIterator($processors, ['group1', 'group2', 'group3'], $context);

        self::assertProcessors(
            ['processor1' => 'group1'],
            $iterator
        );
    }
}
