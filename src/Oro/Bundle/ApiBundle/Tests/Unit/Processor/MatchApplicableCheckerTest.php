<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Oro\Bundle\FormBundle\Entity\PriorityItem;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use PHPUnit\Framework\TestCase;

class MatchApplicableCheckerTest extends TestCase
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

    private function getProcessorIterator(array $processors, Context $context): ProcessorIterator
    {
        return new ProcessorIterator(
            $processors,
            $context,
            $this->getApplicableChecker(),
            $this->getProcessorRegistry()
        );
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

    public function testMatchByEqualTo(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('attr', Entity\User::class);

        $processors = [
            [
                'processor1',
                []
            ],
            [
                'processor2',
                ['attr' => Entity\User::class]
            ],
            [
                'processor3',
                ['attr' => Entity\UserInterface::class]
            ],
            [
                'processor3',
                ['attr' => Entity\Role::class]
            ]
        ];

        $this->assertProcessors(
            [
                'processor1',
                'processor2'
            ],
            $this->getProcessorIterator($processors, $context)
        );
    }

    public function testMatchByInstanceOf(): void
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

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3'
            ],
            $this->getProcessorIterator($processors, $context)
        );
    }

    public function testMatchConcreteEnumOptionInContext(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', 'Extend\Entity\EV_Test_Enum');

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
                ['class' => 'Extend\Entity\EV_Test_Enum']
            ],
            [
                'processor4',
                ['class' => 'Extend\Entity\EV_Another_Enum']
            ],
            [
                'processor5',
                ['class' => EnumOption::class]
            ],
            [
                'processor6',
                ['class' => EnumOptionInterface::class]
            ],
            [
                'processor7',
                ['class' => PriorityItem::class]
            ],
            [
                'processor8',
                ['class' => PrimaryItem::class]
            ]
        ];

        $this->assertProcessors(
            [
                'processor1',
                'processor3',
                'processor5',
                'processor6',
                'processor7'
            ],
            $this->getProcessorIterator($processors, $context)
        );
    }

    public function testMatchBaseEnumOptionInContext(): void
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', EnumOption::class);

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
                ['class' => 'Extend\Entity\EV_Test_Enum']
            ],
            [
                'processor4',
                ['class' => 'Extend\Entity\EV_Another_Enum']
            ],
            [
                'processor5',
                ['class' => EnumOption::class]
            ],
            [
                'processor6',
                ['class' => EnumOptionInterface::class]
            ],
            [
                'processor7',
                ['class' => PriorityItem::class]
            ],
            [
                'processor8',
                ['class' => PrimaryItem::class]
            ]
        ];

        $this->assertProcessors(
            [
                'processor1'
            ],
            $this->getProcessorIterator($processors, $context)
        );
    }
}
