<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\ImportStrategyListener;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportStrategyListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    private $strategyHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var ImportStrategyListener */
    private $listener;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($message) {
                return $message . '.trans';
            });
        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new ImportStrategyListener(
            $this->translator,
            $this->strategyHelper,
            $this->configManager
        );
    }

    public function testOnProcessAfterWhenWrongEntity()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $context->expects($this->never())
            ->method('getValue');
        $context->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $this->configManager->expects($this->never())
            ->method('createFieldConfigByModel');
        $this->strategyHelper->expects($this->never())
            ->method('addValidationErrors');

        $event = new StrategyEvent($strategy, $entity, $context);
        $this->listener->onProcessAfter($event);
        self::assertNotNull($event->getEntity());
    }

    public function testOnProcessAfterWhenNoExistingEntity()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $entity = new FieldConfigModel();
        $context->expects($this->once())
            ->method('getValue')
            ->with('existingEntity')
            ->willReturn(null);
        $context->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $this->configManager->expects($this->never())
            ->method('createFieldConfigByModel');
        $this->strategyHelper->expects($this->never())
            ->method('addValidationErrors');

        $event = new StrategyEvent($strategy, $entity, $context);
        $this->listener->onProcessAfter($event);
        self::assertNotNull($event->getEntity());
    }

    public function testOnProcessAfterWhenNotAttribute()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $entity = new FieldConfigModel();
        $existingEntity = new FieldConfigModel();
        $context->expects($this->once())
            ->method('getValue')
            ->with('existingEntity')
            ->willReturn($existingEntity);

        $config = $this->createMock(ConfigInterface::class);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($entity, 'attribute')
            ->willReturn($config);
        $context->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->never())
            ->method('addValidationErrors');

        $event = new StrategyEvent($strategy, $entity, $context);
        $this->listener->onProcessAfter($event);
        self::assertNotNull($event->getEntity());
    }

    public function testOnProcessAfterWhenExistingIsAttributeToo()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $entity = new FieldConfigModel();
        $existingEntity = new FieldConfigModel();
        $context->expects($this->once())
            ->method('getValue')
            ->with('existingEntity')
            ->willReturn($existingEntity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);
        $existingConfig = $this->createMock(ConfigInterface::class);
        $existingConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('createFieldConfigByModel')
            ->withConsecutive(
                [$entity, 'attribute'],
                [$existingEntity, 'attribute']
            )
            ->willReturnOnConsecutiveCalls($config, $existingConfig);
        $context->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->never())
            ->method('addValidationErrors');

        $event = new StrategyEvent($strategy, $entity, $context);
        $this->listener->onProcessAfter($event);
        self::assertNotNull($event->getEntity());
    }

    public function testOnProcessAfter()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $entity = new FieldConfigModel();
        $existingEntity = new FieldConfigModel();
        $context->expects($this->once())
            ->method('getValue')
            ->with('existingEntity')
            ->willReturn($existingEntity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);
        $existingConfig = $this->createMock(ConfigInterface::class);
        $this->configManager->expects($this->exactly(2))
            ->method('createFieldConfigByModel')
            ->withConsecutive(
                [$entity, 'attribute'],
                [$existingEntity, 'attribute']
            )
            ->willReturnOnConsecutiveCalls($config, $existingConfig);
        $context->expects($this->once())
            ->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['oro.entity_config.import.message.cant_replace_extend_field.trans'], $context);

        $event = new StrategyEvent($strategy, $entity, $context);
        $this->listener->onProcessAfter($event);
        self::assertNull($event->getEntity());
    }
}
