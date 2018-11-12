<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesWithoutIdentifierLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var ResourcesWithoutIdentifierLoader */
    private $loader;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->loader = new ResourcesWithoutIdentifierLoader($this->configProvider);
    }

    public function testLoadForEntityWithoutIdentifierFields()
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
                [new EntityDefinitionConfigExtra(ApiActions::GET), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($config);

        self::assertEquals(
            ['Test\Entity1'],
            $this->loader->load($version, $requestType, $resources)
        );
    }

    public function testLoadForEntityWithIdentifierFields()
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
                [new EntityDefinitionConfigExtra(ApiActions::GET), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($config);

        self::assertEquals(
            [],
            $this->loader->load($version, $requestType, $resources)
        );
    }
}
