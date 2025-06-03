<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\RootPathConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessor;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TargetMetadataAccessorTest extends TestCase
{
    private const string VERSION = '1.0';

    private RequestType $requestType;
    private MetadataProvider&MockObject $metadataProvider;
    private array $metadataExtras;
    private ConfigProvider&MockObject $configProvider;
    private TargetMetadataAccessor $targetMetadataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestType = new RequestType([RequestType::REST]);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->metadataExtras = [$this->createMock(MetadataExtraInterface::class)];
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->targetMetadataAccessor = new TargetMetadataAccessor(
            self::VERSION,
            $this->requestType,
            $this->metadataProvider,
            $this->metadataExtras,
            $this->configProvider,
            [
                $this->createMock(ConfigExtraInterface::class),
                $this->createMock(FilterIdentifierFieldsConfigExtra::class),
                $this->createMock(FilterFieldsConfigExtra::class),
                new ExpandRelatedEntitiesConfigExtra(['expandedAssociation.name'])
            ]
        );
    }

    public function testGetTargetMetadataWhenConfigNotFound(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'association';

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn(new Config());
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath));
    }

    public function testGetTargetMetadataWhenConfigFound(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'association';
        $config = new Config();
        $configDefinition = new EntityDefinitionConfig();
        $config->setDefinition($configDefinition);
        $metadata = new EntityMetadata($targetClassName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn($config);
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                $configDefinition,
                $this->metadataExtras
            )
            ->willReturn($metadata);

        self::assertSame(
            $metadata,
            $this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath)
        );
    }

    public function testGetTargetMetadataWhenConfigFoundButMetadataNotFound(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'association';
        $config = new Config();
        $configDefinition = new EntityDefinitionConfig();
        $config->setDefinition($configDefinition);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn($config);
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                $configDefinition,
                $this->metadataExtras
            )
            ->willReturn(null);

        self::assertNull($this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath));
    }

    public function testGetTargetMetadataWhenFullModeEnabled(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'association';
        $config = new Config();
        $configDefinition = new EntityDefinitionConfig();
        $config->setDefinition($configDefinition);
        $metadata = new EntityMetadata($targetClassName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn($config);
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                $configDefinition,
                $this->metadataExtras
            )
            ->willReturn($metadata);

        self::assertSame(
            $metadata,
            $this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath)
        );
    }

    public function testGetTargetMetadataWhenFullModeDisabledAndAssociationExpandRequested(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'expandedAssociation';
        $config = new Config();
        $configDefinition = new EntityDefinitionConfig();
        $config->setDefinition($configDefinition);
        $metadata = new EntityMetadata($targetClassName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    $this->createMock(FilterIdentifierFieldsConfigExtra::class),
                    $this->createMock(FilterFieldsConfigExtra::class),
                    new ExpandRelatedEntitiesConfigExtra(['name']),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn($config);
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                $configDefinition,
                $this->metadataExtras
            )
            ->willReturn($metadata);

        $this->targetMetadataAccessor->setFullMode(false);
        self::assertSame(
            $metadata,
            $this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath)
        );
    }

    public function testGetTargetMetadataWhenFullModeDisabledAndAssociationIsNotExpandRequested(): void
    {
        $targetClassName = 'Entity\TargetClass';
        $associationPath = 'association';
        $config = new Config();
        $configDefinition = new EntityDefinitionConfig();
        $config->setDefinition($configDefinition);
        $metadata = new EntityMetadata($targetClassName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                [
                    $this->createMock(ConfigExtraInterface::class),
                    $this->createMock(FilterIdentifierFieldsConfigExtra::class),
                    $this->createMock(FilterFieldsConfigExtra::class),
                    new RootPathConfigExtra($associationPath)
                ]
            )
            ->willReturn($config);
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $targetClassName,
                self::VERSION,
                $this->requestType,
                $configDefinition,
                $this->metadataExtras
            )
            ->willReturn($metadata);

        $this->targetMetadataAccessor->setFullMode(false);
        self::assertSame(
            $metadata,
            $this->targetMetadataAccessor->getTargetMetadata($targetClassName, $associationPath)
        );
    }

    public function testFullMode(): void
    {
        self::assertTrue($this->targetMetadataAccessor->isFullMode());

        $this->targetMetadataAccessor->setFullMode(false);
        self::assertFalse($this->targetMetadataAccessor->isFullMode());

        $this->targetMetadataAccessor->setFullMode();
        self::assertTrue($this->targetMetadataAccessor->isFullMode());
    }
}
