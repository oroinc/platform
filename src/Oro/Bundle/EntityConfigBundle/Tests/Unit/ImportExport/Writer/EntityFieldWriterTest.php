<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\EntityFieldWriter;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityFieldStateChecker;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EntityFieldWriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ConfigTranslationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translationHelper;

    /**
     * @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $enumSynchronizer;

    /**
     * @var EntityFieldStateChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stateChecker;

    /**
     * @var EntityFieldWriter
     */
    private $writer;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translationHelper = $this->createMock(ConfigTranslationHelper::class);
        $this->enumSynchronizer = $this->createMock(EnumSynchronizer::class);
        $this->stateChecker = $this->createMock(EntityFieldStateChecker::class);

        $this->writer = new EntityFieldWriter(
            $this->configManager,
            $this->translationHelper,
            $this->enumSynchronizer,
            $this->stateChecker
        );
    }

    public function testWriteDuplicateEnumOptions()
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
                    6 => ['id' => '', 'label' => 'Option Two']
                ]
            ]
        );
        $entityClassName = 'Test';
        $entityModel = new EntityConfigModel($entityClassName);
        $item->setEntity($entityModel);

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($entityClassName, $fieldName)
            ->willReturn(true);

        $this->configManager->expects($this->any())
            ->method('getProviders')
            ->willReturn([]);

        $provider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['enum', $provider]
            ]);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('get')
            ->with('enum_code')
            ->willReturn($enumCode);
        $provider->expects($this->any())
            ->method('getConfig')
            ->with($entityClassName, $fieldName)
            ->willReturn($config);
        $provider->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);
        $this->translationHelper->expects($this->any())
            ->method('getLocale')
            ->willReturn('fr');

        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumOptions')
            ->with(
                ExtendHelper::buildEnumValueClassName($enumCode),
                [
                    0 => ['id' => 'test_id','label' => 'Test ID'],
                    1 => ['id' => '','label' => 'Option Two'],
                    2 => ['id' => '','label' => 'Option Three'],
                    3 => ['id' => '', 'label' => 'option two'],
                    5 => ['id' => 'test_id_2','label' => 'Test ID 2']
                ],
                'fr'
            );

        $this->writer->write([$item]);
    }
}
