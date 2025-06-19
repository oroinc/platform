<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiDocMetadataTest extends TestCase
{
    private string $action;
    private EntityMetadata&MockObject $metadata;
    private EntityDefinitionConfig&MockObject $config;
    private RequestType&MockObject $requestType;
    private ApiDocMetadata $apiDocMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = 'testAction';
        $this->metadata = $this->createMock(EntityMetadata::class);
        $this->config = $this->createMock(EntityDefinitionConfig::class);
        $this->requestType = $this->createMock(RequestType::class);

        $this->apiDocMetadata = new ApiDocMetadata($this->action, $this->metadata, $this->config, $this->requestType);
    }

    public function testGetAction(): void
    {
        self::assertEquals($this->action, $this->apiDocMetadata->getAction());
    }

    public function testGetMetadata(): void
    {
        self::assertEquals($this->metadata, $this->apiDocMetadata->getMetadata());
    }

    public function testGetConfig(): void
    {
        self::assertEquals($this->config, $this->apiDocMetadata->getConfig());
    }

    public function testGetRequestType(): void
    {
        self::assertEquals($this->requestType, $this->apiDocMetadata->getRequestType());
    }

    public function testSerialize(): void
    {
        self::assertSame([], $this->apiDocMetadata->__serialize());
    }
}
