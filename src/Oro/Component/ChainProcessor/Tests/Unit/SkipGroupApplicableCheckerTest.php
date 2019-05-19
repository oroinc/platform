<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\SkipGroupApplicableChecker;

class SkipGroupApplicableCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testSkipGroupApplicableChecker()
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

    /**
     * @return ChainApplicableChecker
     */
    protected function getApplicableChecker()
    {
        $checker = new ChainApplicableChecker();
        $checker->addChecker(new SkipGroupApplicableChecker());

        return $checker;
    }

    /**
     * @param callable[] $callbacks
     *
     * @return ProcessorRegistryInterface
     */
    protected function getProcessorRegistry(array $callbacks = [])
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processorRegistry->expects($this->any())
            ->method('getProcessor')
            ->willReturnCallback(
                function ($processorId) use ($callbacks) {
                    return new ProcessorMock(
                        $processorId,
                        isset($callbacks[$processorId]) ? $callbacks[$processorId] : null
                    );
                }
            );

        return $processorRegistry;
    }

    /**
     * @param string[]         $expectedProcessorIds
     * @param \Iterator        $processors
     * @param ContextInterface $context
     */
    protected function assertProcessors(array $expectedProcessorIds, \Iterator $processors, ContextInterface $context)
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processor->process($context);
            $processorIds[] = $processor->getProcessorId();
        }

        $this->assertEquals($expectedProcessorIds, $processorIds);
    }
}
