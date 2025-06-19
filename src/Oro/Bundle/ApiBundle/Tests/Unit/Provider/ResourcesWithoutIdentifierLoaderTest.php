<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResourcesWithoutIdentifierLoaderTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private ResourcesWithoutIdentifierLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->loader = new ResourcesWithoutIdentifierLoader($this->configProvider);
    }

    public function testLoadForEntityWithoutIdentifierFields(): void
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        $resources = [new ApiResource('Test\Entity1')];

        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Entity1',
                $version,
                $requestType,
                [new EntityDefinitionConfigExtra(ApiAction::GET), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($config);

        self::assertEquals(
            ['Test\Entity1'],
            $this->loader->load($version, $requestType, $resources)
        );
    }

    public function testLoadForEntityWithIdentifierFields(): void
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        $resources = [new ApiResource('Test\Entity1')];

        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());
        $config->getDefinition()->setIdentifierFieldNames(['id']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Entity1',
                $version,
                $requestType,
                [new EntityDefinitionConfigExtra(ApiAction::GET), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($config);

        self::assertEquals(
            [],
            $this->loader->load($version, $requestType, $resources)
        );
    }
}
