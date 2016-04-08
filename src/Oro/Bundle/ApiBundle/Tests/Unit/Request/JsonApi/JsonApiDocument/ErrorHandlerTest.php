<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi\JsonApiDocument;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocument\ErrorHandler;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ErrorHandler */
    protected $handler;

    /** @var Error */
    protected $error;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    protected function setUp()
    {
        $this->handler = new ErrorHandler();

        $this->error = new Error();
        $this->error->setStatusCode(500);
        $this->error->setDetail('test detail');
        $this->error->setTitle('title');

        $this->metadata = $this->getMockBuilder('Oro\Bundle\ApiBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $fieldMetadata = $this->getMockBuilder('Oro\Bundle\ApiBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata->expects($this->any())
            ->method('getFields')
            ->willReturn(
                [
                    'id' => $fieldMetadata,
                    'firstName' => $fieldMetadata
                ]
            );
        $this->metadata->expects($this->any())
            ->method('getAssociations')
            ->willReturn(
                [
                    'user' => $fieldMetadata
                ]
            );
    }

    public function testHandleError()
    {
        $this->error->setPropertyName('property');
        $result = $this->handler->handleError($this->error);
        $this->assertEquals(
            [
                'code' => '500',
                'title' => 'title',
                'detail' => 'test detail Source: property'
            ],
            $result
        );
    }

    /**
     * @dataProvider propertyDataProvider
     */
    public function testHandleErrorWithIdProperty($propertyName, $expectedResult)
    {
        $this->error->setPropertyName($propertyName);
        $result = $this->handler->handleError($this->error, $this->metadata);
        $this->assertEquals($expectedResult, $result);
    }

    public function propertyDataProvider()
    {
        return [
            [
                'id',
                [
                    'code' => '500',
                    'title' => 'title',
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/id']
                ]
            ],
            [
                'firstName',
                [
                    'code' => '500',
                    'title' => 'title',
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/attributes/firstName']
                ]
            ],
            [
                'user',
                [
                    'code' => '500',
                    'title' => 'title',
                    'detail' => 'test detail',
                    'source' => ['pointer' => '/data/relationships/user']
                ]
            ],
            [
                'nonMappedPointer',
                [
                    'code' => '500',
                    'title' => 'title',
                    'detail' => 'test detail Source: nonMappedPointer'
                ]
            ]
        ];
    }
}
