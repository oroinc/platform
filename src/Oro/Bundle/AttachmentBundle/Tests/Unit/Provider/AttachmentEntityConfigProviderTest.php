<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Psr\Log\LoggerInterface;

class AttachmentEntityConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AttachmentEntityConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new AttachmentEntityConfigProvider($this->entityConfigManager, $this->logger);
    }

    public function testGetFieldConfigWhenNoConfig(): void
    {
        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass', $fieldName = 'sampleFieldName')
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
        $config = $this->createMock(ConfigInterface::class);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass', $fieldName = 'sampleFieldName')
            ->willReturn(true);
        $this->entityConfigManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn($config);

        self::assertEquals($config, $this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function testGetEntityConfigWhenNoConfig(): void
    {
        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass')
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
        $config = $this->createMock(ConfigInterface::class);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass')
            ->willReturn(true);
        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config);

        $this->assertEquals($config, $this->provider->getEntityConfig($entityClass));
    }
}
