<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;

class MatchApplicableCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testMatchByInstanceOf()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', Entity\User::class);

        $processors = [
            [
                'processor1',
                []
            ],
            [
                'processor2',
                ['class' => Entity\User::class]
            ],
            [
                'processor3',
                ['class' => Entity\UserInterface::class]
            ],
            [
                'processor3',
                ['class' => Entity\Role::class]
            ]
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
                'processor3'
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
        $factory = $this->createMock(ProcessorFactoryInterface::class);
        $factory->expects(self::any())
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

        self::assertEquals($expectedProcessorIds, $processorIds);
    }
}
