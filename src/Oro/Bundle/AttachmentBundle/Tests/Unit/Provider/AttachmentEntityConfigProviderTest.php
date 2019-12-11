<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class AttachmentEntityConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var EntityConfigManager */
    private $entityConfigManager;

    /** @var AttachmentEntityConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new AttachmentEntityConfigProvider($this->entityConfigManager);

        $this->setUpLoggerMock($this->provider);
    }

    public function testGetFieldConfigWhenNoConfig(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass', $fieldName = 'sampleFieldName')
            ->willReturn(false);

        $this->assertLoggerWarningMethodCalled();

        $this->assertNull($this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function testGetFieldConfig(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass', $fieldName = 'sampleFieldName')
            ->willReturn(true);

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $this->assertEquals($config, $this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function testGetEntityConfigWhenNoConfig(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass')
            ->willReturn(false);

        $this->assertLoggerWarningMethodCalled();

        $this->assertNull($this->provider->getEntityConfig($entityClass));
    }

    public function testGetEntityConfig(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = 'SampleClass')
            ->willReturn(true);

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $entityClass)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $this->assertEquals($config, $this->provider->getEntityConfig($entityClass));
    }
}
