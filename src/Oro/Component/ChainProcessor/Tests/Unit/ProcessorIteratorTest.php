<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorIteratorFactory;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use PHPUnit\Framework\TestCase;

class ProcessorIteratorTest extends TestCase
{
    private const TEST_ACTION = 'test_action';

    private function getProcessorIterator(
        array $processors,
        Context $context,
        ?ApplicableCheckerInterface $applicableChecker = null,
        bool $withApplicableCache = false
    ): ProcessorIterator {
        $factory = new ProcessorIteratorFactory($withApplicableCache ? [self::TEST_ACTION] : []);

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

    private static function assertProcessors(array $expectedProcessorIds, \Iterator $processors): void
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        self::assertSame($expectedProcessorIds, $processorIds);
    }

    public function testEmptyIterator(): void
    {
        $iterator = $this->getProcessorIterator([], $this->getContext());

        self::assertProcessors([], $iterator);
    }

    public function testIterator(): void
    {
        $processors = [
            ['processor1', []],
            ['processor2', ['disabled' => true]],
            ['processor3', []]
        ];

        $iterator = $this->getProcessorIterator(
            $processors,
            $this->getContext(),
            new NotDisabledApplicableChecker()
        );

        self::assertProcessors(
            [
                'processor1',
                'processor3'
            ],
            $iterator
        );
    }

    public function testIteratorWithApplicableCache(): void
    {
        $processors = [
            ['processor1', []],
            ['processor2', ['disabled' => true]],
            ['processor3', []]
        ];

        $iterator = $this->getProcessorIterator(
            $processors,
            $this->getContext(),
            new NotDisabledApplicableChecker(),
            true
        );

        self::assertProcessors(
            [
                'processor1',
                'processor3'
            ],
            $iterator
        );
    }

    public function testApplicableCheckerGetterAndSetter(): void
    {
        $applicableChecker = new ChainApplicableChecker();

        $iterator = $this->getProcessorIterator([], $this->getContext(), $applicableChecker);

        self::assertSame($applicableChecker, $iterator->getApplicableChecker());

        $newApplicableChecker = new ChainApplicableChecker();
        $iterator->setApplicableChecker($newApplicableChecker);
        self::assertSame($newApplicableChecker, $iterator->getApplicableChecker());
    }

    public function testServiceProperties(): void
    {
        $processors = [
            ['processor1', ['group' => 'group1', 'attr1' => 'val1']],
            ['processor2', ['group' => 'group2', 'attr1' => 'val1']]
        ];

        $iterator = $this->getProcessorIterator($processors, $this->getContext());

        $iterator->rewind();
        self::assertEquals('processor1', $iterator->getProcessorId());
        self::assertEquals(self::TEST_ACTION, $iterator->getAction());
        self::assertEquals('group1', $iterator->getGroup());
        self::assertEquals(['group' => 'group1', 'attr1' => 'val1'], $iterator->getProcessorAttributes());

        $iterator->next();
        self::assertEquals('processor2', $iterator->getProcessorId());
        self::assertEquals(self::TEST_ACTION, $iterator->getAction());
        self::assertEquals('group2', $iterator->getGroup());
        self::assertEquals(['group' => 'group2', 'attr1' => 'val1'], $iterator->getProcessorAttributes());
    }
}
