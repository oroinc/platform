<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

class ProcessorIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyIterator()
    {
        $context = new Context();
        $processors = [];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorFactory()
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
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => []]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorFactory()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
            ],
            $iterator
        );
    }

    public function testUnknownProcessor()
    {
        $context = new Context();
        $processors = [
            ['processor' => 'processor1', 'attributes' => []],
            ['processor' => 'processor2', 'attributes' => []],
            ['processor' => 'processor3', 'attributes' => []]
        ];

        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->at(0))
            ->method('getProcessor')
            ->with('processor1')
            ->willReturn(new ProcessorMock('processor1'));
        $factory->expects($this->at(1))
            ->method('getProcessor')
            ->with('processor2')
            ->willReturn(null);
        $factory->expects($this->at(2))
            ->method('getProcessor')
            ->with('processor3')
            ->willReturn(new ProcessorMock('processor3'));

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $factory
        );

        $iterator->rewind();
        $this->assertEquals(new ProcessorMock('processor1'), $iterator->current());
        $this->assertTrue($iterator->valid());

        $iterator->next();
        try {
            $iterator->current();
        } catch (\RuntimeException $e) {
            $this->assertEquals('The processor "processor2" does not exist.', $e->getMessage());
        }
        $this->assertTrue($iterator->valid());

        $iterator->next();
        $this->assertEquals(new ProcessorMock('processor3'), $iterator->current());
        $this->assertTrue($iterator->valid());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    public function testApplicableCheckerGetterAndSetter()
    {
        $applicableChecker = new ChainApplicableChecker();

        $iterator = new ProcessorIterator(
            [],
            new Context(),
            $applicableChecker,
            $this->getProcessorFactory()
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
            [
                'processor'  => 'processor1',
                'attributes' => ['group' => 'group1', 'attr1' => 'val1']
            ],
            [
                'processor'  => 'processor2',
                'attributes' => ['group' => 'group2', 'attr1' => 'val1']
            ]
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            new ChainApplicableChecker(),
            $this->getProcessorFactory()
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
