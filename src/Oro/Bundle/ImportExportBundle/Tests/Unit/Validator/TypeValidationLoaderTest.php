<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Validator\TypeValidationLoader;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TypeValidationLoaderTest extends \PHPUnit\Framework\TestCase
{
    private const INTEGER_CONSTRAINT = [
        [
            'Regex' => [
                'pattern' => '/^\-{0,1}[\d+]*$/',
                'message' => 'This value should contain only numbers.',
                'groups' => ['import_field_type']
            ]
        ]
    ];

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigProvider;

    /** @var TypeValidationLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->fieldConfigProvider = $this->createMock(ConfigProvider::class);

        $this->loader = new TypeValidationLoader($this->extendConfigProvider, $this->fieldConfigProvider);
    }

    public function testLoadMetadataWhenNoConstraints(): void
    {
        $className = \stdClass::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->fieldConfigProvider->expects($this->never())
            ->method('getConfigs');

        $this->loader->loadClassMetadata($classMetaData);
    }

    public function testLoadMetadataAndEntityHasNoConfig(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = \stdClass::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->fieldConfigProvider->expects($this->never())
            ->method('getConfigs')
            ->with($className);

        $this->loader->loadClassMetadata($classMetaData);
    }

    public function testLoadMetadataEntityHasNoProperty(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn($this->getFieldConfigs($className, ['invalidProperty' => 'integer']));

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(0, $classMetaData->getConstrainedProperties());
    }

    public function testLoadMetadataFieldIsDeleted(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn($this->getFieldConfigs($className, ['id' => 'integer']));

        $extendConfigFieldMock = $this->createMock(ConfigInterface::class);
        $extendConfigFieldMock->expects($this->once())
            ->method('is')
            ->with('is_deleted')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, 'id')
            ->willReturn($extendConfigFieldMock);

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(0, $classMetaData->getConstrainedProperties());
    }

    public function testLoadMetadataFieldIsNotActive(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn($this->getFieldConfigs($className, ['id' => 'integer']));

        $extendConfigFieldMock = $this->createMock(ConfigInterface::class);
        $extendConfigFieldMock->expects($this->exactly(2))
            ->method('is')
            ->withConsecutive(
                ['is_deleted'],
                ['state', 'Active']
            )
            ->willReturnOnConsecutiveCalls(false, false);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, 'id')
            ->willReturn($extendConfigFieldMock);

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(0, $classMetaData->getConstrainedProperties());
    }

    public function testLoadMetadataFieldIsExcluded(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn($this->getFieldConfigs($className, ['id' => 'integer'], ['id' => ['excluded' => true]]));

        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(0, $classMetaData->getConstrainedProperties());
    }

    public function testLoadMetadata(): void
    {
        $this->loader->addConstraints('integer', self::INTEGER_CONSTRAINT);

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn($this->getFieldConfigs($className, ['id' => 'integer']));

        $extendConfigFieldMock = $this->createMock(ConfigInterface::class);
        $extendConfigFieldMock->expects($this->exactly(2))
            ->method('is')
            ->withConsecutive(
                ['is_deleted'],
                ['state', 'Active']
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, 'id')
            ->willReturn($extendConfigFieldMock);

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(1, $classMetaData->getConstrainedProperties());
    }

    private function getFieldConfigs(string $className, array $fieldConfigArray, array $config = []): array
    {
        $fieldConfigs = [];
        foreach ($fieldConfigArray as $fieldName => $fieldType) {
            $fieldConfigs[] = new Config(
                new FieldConfigId('app', $className, $fieldName, $fieldType),
                $config[$fieldName] ?? []
            );
        }

        return $fieldConfigs;
    }
}
