<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;

class MatchApplicableCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchByInstanceOf()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');

        $processors = [
            [
                'processor'  => 'processor1',
                'attributes' => []
            ],
            [
                'processor'  => 'processor2',
                'attributes' => ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User']
            ],
            [
                'processor'  => 'processor3',
                'attributes' => ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserInterface']
            ],
            [
                'processor'  => 'processor3',
                'attributes' => ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Role']
            ],
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $this->getApplicableChecker(),
            $this->getProcessorFactory()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
            ],
            $iterator
        );
    }

    /**
     * @return ChainApplicableChecker
     */
    protected function getApplicableChecker()
    {
        $checker = new ChainApplicableChecker();
        $checker->addChecker(new MatchApplicableChecker(['group'], ['class']));

        return $checker;
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
