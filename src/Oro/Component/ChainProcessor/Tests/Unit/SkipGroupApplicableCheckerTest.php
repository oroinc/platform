<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\SkipGroupApplicableChecker;
use PHPUnit\Framework\TestCase;

class SkipGroupApplicableCheckerTest extends TestCase
{
    public function testSkipGroupApplicableChecker(): void
    {
        $context = new Context();
        $processors = [
            ['processor1', ['group' => 'group1']],
            ['processor2', ['group' => 'group2']],
            ['processor3', ['group' => 'group2']],
            ['processor4', ['group' => 'group3']]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $this->getApplicableChecker(),
            $this->getProcessorRegistry(
                [
                    'processor1' => function (ContextInterface $context) {
                        $context->skipGroup('group2');
                    }
                ]
            )
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor4',
            ],
            $iterator,
            $context
        );
    }

    private function getApplicableChecker(): ChainApplicableChecker
    {
        $checker = new ChainApplicableChecker();
        $checker->addChecker(new SkipGroupApplicableChecker());

        return $checker;
    }

    private function getProcessorRegistry(array $callbacks = []): ProcessorRegistryInterface
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnCallback(function ($processorId) use ($callbacks) {
                return new ProcessorMock(
                    $processorId,
                    $callbacks[$processorId] ?? null
                );
            });

        return $processorRegistry;
    }

    private function assertProcessors(
        array $expectedProcessorIds,
        \Iterator $processors,
        ContextInterface $context
    ): void {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processor->process($context);
            $processorIds[] = $processor->getProcessorId();
        }

        self::assertEquals($expectedProcessorIds, $processorIds);
    }
}
