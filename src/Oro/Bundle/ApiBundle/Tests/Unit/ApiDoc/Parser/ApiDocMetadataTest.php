<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ApiDocMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $action;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|EntityMetadata */
    private $metadata;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityDefinitionConfig */
    private $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestType */
    private $requestType;

    /** @var ApiDocMetadata */
    private $apiDocMetadata;

    protected function setUp()
    {
        $this->action = 'testAction';
        $this->metadata = $this->createMock(EntityMetadata::class);
        $this->config = $this->createMock(EntityDefinitionConfig::class);
        $this->requestType = $this->createMock(RequestType::class);

        $this->apiDocMetadata = new ApiDocMetadata($this->action, $this->metadata, $this->config, $this->requestType);
    }

    public function testGetAction()
    {
        self::assertEquals($this->action, $this->apiDocMetadata->getAction());
    }

    public function testGetMetadata()
    {
        self::assertEquals($this->metadata, $this->apiDocMetadata->getMetadata());
    }

    public function testGetConfig()
    {
        self::assertEquals($this->config, $this->apiDocMetadata->getConfig());
    }

    public function testGetRequestType()
    {
        self::assertEquals($this->requestType, $this->apiDocMetadata->getRequestType());
    }

    public function testSerialize()
    {
        self::assertSame('', $this->apiDocMetadata->serialize());
    }
}
