<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;

class MatchApplicableCheckerTest extends \PHPUnit\Framework\TestCase
{
    private function getApplicableChecker(): ChainApplicableChecker
    {
        $checker = new ChainApplicableChecker();
        $checker->addChecker(new MatchApplicableChecker(['group'], ['class']));

        return $checker;
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

    private function assertProcessors(array $expectedProcessorIds, \Iterator $processors): void
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        self::assertEquals($expectedProcessorIds, $processorIds);
    }

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
            $this->getProcessorRegistry()
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
}
