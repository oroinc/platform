<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ExtractEntityId;
use Oro\Bundle\ApiBundle\Request\Constraint;
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

    public function testProcessForEmptyRequestDataWithoutEntityIdButEntityHasIdGenerator()
    {
        $requestData = [
            'data' => []
        ];
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessForEmptyRequestDataWithoutEntityIdAndEntityDoesNotHaveIdGenerator()
    {
        $requestData = [
            'data' => []
        ];
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(false);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
        $this->assertEquals(
            [
                Error::createValidationError(Constraint::ENTITY_ID, 'The identifier is mandatory')
                    ->setSource(ErrorSource::createByPropertyPath('id'))
            ],
            $this->context->getErrors()
        );
    }
}
