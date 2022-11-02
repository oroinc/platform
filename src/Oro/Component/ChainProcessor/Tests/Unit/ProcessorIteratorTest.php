<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

class ProcessorIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyIterator()
    {
        $context = new Context();
        $processors = [];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorRegistry()
        );

        $this->assertProcessors(
            [],
            $iterator
        );
    }

    public function testProcessorsForKnownAction()
    {
        $context = new Context();
        $processors = [
            ['processor1', []],
            ['processor2', []]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorRegistry()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
            ],
            $iterator
        );
    }

    public function testApplicableCheckerGetterAndSetter()
    {
        $applicableChecker = new ChainApplicableChecker();

        $iterator = new ProcessorIterator(
            [],
            new Context(),
            $applicableChecker,
            $this->getProcessorRegistry()
        );

        $this->assertSame($applicableChecker, $iterator->getApplicableChecker());

        $newApplicableChecker = new ChainApplicableChecker();
        $iterator->setApplicableChecker($newApplicableChecker);
        $this->assertSame($newApplicableChecker, $iterator->getApplicableChecker());
    }

    public function testServiceProperties()
    {
        $context = new Context();
        $context->setAction('action1');

        $processors = [
            ['processor1', ['group' => 'group1', 'attr1' => 'val1']],
            ['processor2', ['group' => 'group2', 'attr1' => 'val1']]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorRegistry()
        );

        $iterator->rewind();
        $this->assertEquals('processor1', $iterator->getProcessorId());
        $this->assertEquals('action1', $iterator->getAction());
        $this->assertEquals('group1', $iterator->getGroup());
        $this->assertEquals(['group' => 'group1', 'attr1' => 'val1'], $iterator->getProcessorAttributes());

        $iterator->next();
        $this->assertEquals('processor2', $iterator->getProcessorId());
        $this->assertEquals('action1', $iterator->getAction());
        $this->assertEquals('group2', $iterator->getGroup());
        $this->assertEquals(['group' => 'group2', 'attr1' => 'val1'], $iterator->getProcessorAttributes());
    }

    /**
     * @return ProcessorRegistryInterface
     */
    protected function getProcessorRegistry()
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processorRegistry->expects($this->any())
            ->method('getProcessor')
            ->willReturnCallback(
                function ($processorId) {
                    return new ProcessorMock($processorId);
                }
            );

        return $processorRegistry;
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
