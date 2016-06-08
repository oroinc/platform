<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ExtractEntityId;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ExtractEntityIdTest extends FormProcessorTestCase
{
    /** @var ExtractEntityId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ExtractEntityId();
    }

    public function testProcessWhenEntityIdAlreadyExistsInContext()
    {
        $entityId = 123;
        $requestData = [
            'data' => [
                'id' => 456
            ]
        ];

        $this->context->setId($entityId);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertEquals($entityId, $this->context->getId());
    }

    public function testProcessWhenEntityIdDoesNotExistInContext()
    {
        $requestData = [
            'data' => [
                'id' => 456
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertEquals(456, $this->context->getId());
    }

    public function testProcessForEmptyRequestData()
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
    }

    public function testProcessForEmptyRequestDataWithoutEntityId()
    {
        $requestData = [
            'data' => []
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
    }
}
