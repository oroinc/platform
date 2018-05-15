<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class ValidateRequestDataTest extends ChangeRelationshipProcessorTestCase
{
    /** @var ValidateRequestData */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateRequestData();
    }

    public function testProcessWhenRequestDataAlreadyValidated()
    {
        $this->context->setRequestData([]);
        $this->context->setProcessed(ValidateRequestData::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithValidRequestDataForToOneAssociation()
    {
        $requestData = [
            'data' => ['type' => 'products', 'id' => '123']
        ];

        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestDataForToManyAssociation()
    {
        $requestData = [
            'data' => [
                ['type' => 'products', 'id' => '123']
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForToOneAssociation()
    {
        $requestData = ['data' => []];

        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The resource identifier object should be not empty object'
        );
        $error->setSource(ErrorSource::createByPointer('/data'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForToManyAssociation()
    {
        $requestData = ['data' => null];

        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The list of resource identifier objects should be an array'
        );
        $error->setSource(ErrorSource::createByPointer('/data'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }
}
