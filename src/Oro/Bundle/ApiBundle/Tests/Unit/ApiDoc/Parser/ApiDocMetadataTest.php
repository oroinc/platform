<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;

class ApiDocMetadataTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $action;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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
        $this->assertEquals('a:0:{}', $this->apiDocMetadata->serialize());
    }
}
