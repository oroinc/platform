<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\SkipGroupApplicableChecker;

class SkipGroupApplicableCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testSkipGroupApplicableChecker()
    {
        $context = new Context();
        $processors = [
            ['processor' => 'processor1', 'attributes' => ['group' => 'group1']],
            ['processor' => 'processor2', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor3', 'attributes' => ['group' => 'group2']],
            ['processor' => 'processor4', 'attributes' => ['group' => 'group3']]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $this->getApplicableChecker(),
            $this->getProcessorFactory(
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
     * @return ProcessorFactoryInterface
     */
    protected function getProcessorFactory(array $callbacks = [])
    {
        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->any())
            ->method('getProcessor')
            ->willReturnCallback(
                function ($processorId) use ($callbacks) {
                    return new ProcessorMock(
                        $processorId,
                        isset($callbacks[$processorId]) ? $callbacks[$processorId] : null
                    );
                }
            );

        return $factory;
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
