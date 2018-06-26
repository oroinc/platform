<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;

class ApiDocMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    protected $action;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestType;

    /** @var ApiDocMetadata */
    protected $apiDocMetadata;

    protected function setUp()
    {
        $this->action = 'testAction';
        $this->metadata = $this->getMockBuilder('Oro\Bundle\ApiBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder('Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestType = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\RequestType')
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiDocMetadata = new ApiDocMetadata($this->action, $this->metadata, $this->config, $this->requestType);
    }

    public function testGetAction()
    {
        $this->assertEquals($this->action, $this->apiDocMetadata->getAction());
    }

    public function testGetMetadata()
    {
        $this->assertEquals($this->metadata, $this->apiDocMetadata->getMetadata());
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->config, $this->apiDocMetadata->getConfig());
    }

    public function testGetRequestType()
    {
        $this->assertEquals($this->requestType, $this->apiDocMetadata->getRequestType());
    }

    public function testSerialize()
    {
        $this->assertSame('', $this->apiDocMetadata->serialize());
    }
}
