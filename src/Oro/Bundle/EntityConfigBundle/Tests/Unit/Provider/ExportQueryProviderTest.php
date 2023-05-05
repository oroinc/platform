<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ExportQueryProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class ExportQueryProviderTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_FIELD = 'fieldName';

    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var ExportQueryProvider */
    private $exportQueryProvider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->exportQueryProvider = new ExportQueryProvider($this->entityConfigManager);
    }

    public function testEnumField(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(TestEnumValue::class);
        $metadata->expects($this->never())
            ->method('isAssociationWithSingleJoinColumn');

        $this->assertFalse($this->exportQueryProvider->isAssociationExportable($metadata, self::DEFAULT_FIELD));
    }

    public function testFallbackField(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(EntityFieldFallbackValue::class);
        $metadata->expects($this->never())
            ->method('isAssociationWithSingleJoinColumn');

        $this->assertFalse($this->exportQueryProvider->isAssociationExportable($metadata, self::DEFAULT_FIELD));
    }

    /**
     * @dataProvider configurableFieldDataProvider
     */
    public function testConfigurableField(bool $isExcluded, bool $isExportable): void
    {
        $this->assertEntityConfigManger($isExcluded);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(TestActivity::class);
        $metadata->expects($this->any())
            ->method('isAssociationWithSingleJoinColumn')
            ->willReturn(true);

        $this->assertEquals(
            $isExportable,
            $this->exportQueryProvider->isAssociationExportable($metadata, self::DEFAULT_FIELD)
        );
    }

    public function configurableFieldDataProvider(): \Generator
    {
        yield 'Field is not excluded' => ['isExcluded' => false, 'isExportable' => true];
        yield 'Field is excluded' => ['isExcluded' => true, 'isExportable' => false];
    }

    private function assertEntityConfigManger(bool $isExcluded): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('has')
            ->willReturn(true);
        $config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['excluded', false, null, $isExcluded]
            ]);

        $this->entityConfigManager->expects($this->any())
            ->method('hasConfig')
            ->with(TestActivity::class, self::DEFAULT_FIELD)
            ->willReturn(true);

        $this->entityConfigManager->expects($this->any())
            ->method('getFieldConfig')
            ->with('importexport', TestActivity::class, self::DEFAULT_FIELD)
            ->willReturn($config);
    }
}
