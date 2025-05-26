<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AttachmentEntityConfigProviderTest extends TestCase
{
    private EntityConfigManager&MockObject $entityConfigManager;
    private LoggerInterface&MockObject $logger;
    private AttachmentEntityConfigProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new AttachmentEntityConfigProvider($this->entityConfigManager, $this->logger);
    }

    public function testGetFieldConfigWhenNoConfig(): void
    {
        $entityClass = 'SampleClass';
        $fieldName = 'sampleFieldName';

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(false);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Attachment entity field config for {entityClass} entity class and {fieldName} field was not found.',
                ['entityClass' => $entityClass, 'fieldName' => $fieldName]
            );

        self::assertNull($this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function testGetFieldConfig(): void
    {
        $entityClass = 'SampleClass';
        $fieldName = 'sampleFieldName';

        $config = $this->createMock(ConfigInterface::class);
        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(true);
        $this->entityConfigManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn($config);

        self::assertEquals($config, $this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function testGetEntityConfigWhenNoConfig(): void
    {
        $entityClass = 'SampleClass';

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Attachment entity config for {entityClass} entity class was not found.',
                ['entityClass' => $entityClass]
            );

        self::assertNull($this->provider->getEntityConfig($entityClass));
    }

    public function testGetEntityConfig(): void
    {
        $entityClass = 'SampleClass';

        $config = $this->createMock(ConfigInterface::class);
        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);

        $this->assertEquals($config, $this->provider->getEntityConfig($entityClass));
    }
}
