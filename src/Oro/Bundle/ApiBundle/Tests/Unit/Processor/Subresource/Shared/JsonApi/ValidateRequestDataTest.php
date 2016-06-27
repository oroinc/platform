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
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateRequestData();
    }

    /**
     * @dataProvider validRequestDataToOneAssociation
     */
    public function testProcessWithValidRequestDataForToOneAssociation($requestData)
    {
        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function validRequestDataToOneAssociation()
    {
        return [
            [
                ['data' => ['type' => 'products', 'id' => '123']]
            ],
            [
                ['data' => null]
            ],
        ];
    }

    /**
     * @dataProvider validRequestDataToManyAssociation
     */
    public function testProcessWithValidRequestDataForToManyAssociation($requestData)
    {
        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function validRequestDataToManyAssociation()
    {
        return [
            [
                ['data' => [['type' => 'products', 'id' => '123']]]
            ],
            [
                ['data' => []]
            ],
        ];
    }

    /**
     * @dataProvider invalidRequestDataToOneAssociation
     */
    public function testProcessWithInvalidRequestDataForToOneAssociation($requestData, $expectedErrors)
    {
        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $errors = [];
        foreach ($expectedErrors as $expectedError) {
            $errors[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        $this->assertEquals(
            $errors,
            $this->context->getErrors()
        );
    }

    public function invalidRequestDataToOneAssociation()
    {
        return [
            [
                [],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                [123],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                ['data' => []],
                [
                    ['The resource identifier object should be not empty object', '/data']
                ]
            ],
            [
                ['data' => 123],
                [
                    ['The resource identifier object should be NULL or an object', '/data']
                ]
            ],
            [
                ['data' => [123]],
                [
                    ['The resource identifier object should be an object', '/data']
                ]
            ],
            [
                ['data' => ['attr1' => 'val1']],
                [
                    ['The \'id\' property is required', '/data/id'],
                    ['The \'type\' property is required', '/data/type']
                ]
            ],
            [
                ['data' => ['type' => null, 'id' => null]],
                [
                    ['The \'id\' property should not be empty', '/data/id'],
                    ['The \'type\' property should not be empty', '/data/type']
                ]
            ],
            [
                ['data' => ['type' => '', 'id' => '']],
                [
                    ['The \'id\' property should not be empty', '/data/id'],
                    ['The \'type\' property should not be empty', '/data/type']
                ]
            ],
            [
                ['data' => ['type' => 123, 'id' => 456]],
                [
                    ['The \'id\' property should be a string', '/data/id'],
                    ['The \'type\' property should be a string', '/data/type']
                ]
            ],
        ];
    }

    /**
     * @dataProvider invalidRequestDataToManyAssociation
     */
    public function testProcessWithInvalidRequestDataForToManyAssociation($requestData, $expectedErrors)
    {
        $this->context->setRequestData($requestData);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $errors = [];
        foreach ($expectedErrors as $expectedError) {
            $errors[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        $this->assertEquals(
            $errors,
            $this->context->getErrors()
        );
    }

    public function invalidRequestDataToManyAssociation()
    {
        return [
            [
                [],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                [123],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                ['data' => null],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => 123],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => [123]],
                [
                    ['The resource identifier object should be an object', '/data/0']
                ]
            ],
            [
                ['data' => ['attr1' => 'val1']],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => [['attr1' => 'val1']]],
                [
                    ['The \'id\' property is required', '/data/0/id'],
                    ['The \'type\' property is required', '/data/0/type']
                ]
            ],
            [
                ['data' => [['type' => null, 'id' => null]]],
                [
                    ['The \'id\' property should not be empty', '/data/0/id'],
                    ['The \'type\' property should not be empty', '/data/0/type']
                ]
            ],
            [
                ['data' => [['type' => '', 'id' => '']]],
                [
                    ['The \'id\' property should not be empty', '/data/0/id'],
                    ['The \'type\' property should not be empty', '/data/0/type']
                ]
            ],
            [
                ['data' => [['type' => 123, 'id' => 456]]],
                [
                    ['The \'id\' property should be a string', '/data/0/id'],
                    ['The \'type\' property should be a string', '/data/0/type']
                ]
            ],
        ];
    }
}
