<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\ImportStrategyListener;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ImportStrategyListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    private $strategyHelper;

    /** @var ImportStrategyListener */
    private $listener;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->strategyHelper = $this->getMockBuilder(ImportStrategyHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new ImportStrategyListener($this->translator, $this->strategyHelper);
    }

    public function testOnProcessAfter()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|StrategyInterface $strategy */
        $strategy = $this->createMock(StrategyInterface::class);
        $entity = new FieldConfigModel();
        /** @var \PHPUnit\Framework\MockObject\MockObject|ContextInterface $context */
        $context = $this->createMock(ContextInterface::class);
        $event = new StrategyEvent($strategy, $entity, $context);

        $entity->fromArray('attribute', ['is_attribute' => false]);
        $context->expects($this->once())->method('hasOption')->with('check_attributes')->willReturn(true);
        $context->expects($this->once())->method('getValue')->with('existingEntity')->willReturn($entity);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.entity_config.import.message.cant_replace_extend_field')
            ->willReturn('ErrorMessage');

        $context->expects($this->once())->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['ErrorMessage'], $context);

        $this->listener->onProcessAfter($event);
    }
}
