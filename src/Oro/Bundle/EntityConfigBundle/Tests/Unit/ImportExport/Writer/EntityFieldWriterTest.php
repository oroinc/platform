<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\EntityFieldWriter;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityFieldStateChecker;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityFieldWriterTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ConfigTranslationHelper|MockObject $translationHelper;

    private EnumSynchronizer|MockObject $enumSynchronizer;

    private EntityFieldStateChecker|MockObject $stateChecker;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private EntityFieldWriter $writer;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translationHelper = $this->createMock(ConfigTranslationHelper::class);
        $this->enumSynchronizer = $this->createMock(EnumSynchronizer::class);
        $this->stateChecker = $this->createMock(EntityFieldStateChecker::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $configHalper = $this->createMock(ConfigHelper::class);

        $this->writer = new EntityFieldWriter(
            $this->configManager,
            $this->translationHelper,
            $this->enumSynchronizer,
            $this->stateChecker,
            $this->eventDispatcher,
            $configHalper
        );
    }

    public function testWriteDuplicateEnumOptions(): void
    {
        $fieldName = 'testField';
        $enumCode = 'test_enum';
        $item = new FieldConfigModel($fieldName, 'enum');
        $item->fromArray(
            'enum',
            [
                'enum_options' => [
                    0 => ['id' => 'test_id', 'label' => 'Test ID'],
                    1 => ['id' => '', 'label' => 'Option Two'],
                    2 => ['id' => '', 'label' => 'Option Three'],
                    3 => ['id' => '', 'label' => 'option two'],
                    4 => ['id' => 'test_id', 'label' => 'Test ID 2'],
                    5 => ['id' => 'test_id_2', 'label' => 'Test ID 2'],
                    6 => ['id' => '', 'label' => 'Option Two'],
                ],
                'enum_code' => $enumCode
            ]
        );
        $entityClassName = 'Test';
        $entityModel = new EntityConfigModel($entityClassName);
        $item->setEntity($entityModel);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClassName, $fieldName)
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('getProviders')
            ->willReturn([]);

        $provider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', []],
                ['enum', $provider],
            ]);

        $provider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(true);
        $this->translationHelper->expects(self::once())
            ->method('getLocale')
            ->willReturn('fr');

        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumOptions')
            ->with(
                $enumCode,
                EnumOption::class,
                [
                    0 => ['id' => 'test_id', 'label' => 'Test ID'],
                    1 => ['id' => '', 'label' => 'Option Two'],
                    2 => ['id' => '', 'label' => 'Option Three'],
                    3 => ['id' => '', 'label' => 'option two'],
                    5 => ['id' => 'test_id_2', 'label' => 'Test ID 2'],
                ],
                'fr'
            );

        $this->writer->write([$item]);
    }
}
