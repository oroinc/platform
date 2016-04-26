<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class NormalizeRequestDataTest extends FormProcessorTestCase
{
    /** @var NormalizeRequestData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    public function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new NormalizeRequestData($this->valueNormalizer, $this->entityIdTransformer);
    }

    public function testProcessForAlreadyNormalizedData()
    {
        $data = ['foo' => 'bar'];
        $this->context->setRequestData($data);
        $this->processor->process($this->context);
        $this->assertSame($data, $this->context->getRequestData());
    }

    public function testProcess()
    {
        $inputData = [
            'data' => [
                'attributes'    => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe'
                ],
                'relationships' => [
                    'toOneRelation'       => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ],
                    'toManyRelation'      => [
                        'data' => [
                            [
                                'type' => 'groups',
                                'id'   => '1'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '2'
                            ],
                            [
                                'type' => 'groups',
                                'id'   => '3'
                            ]
                        ]
                    ],
                    'emptyToOneRelation'  => ['data' => null],
                    'emptyToManyRelation' => ['data' => []]
                ]
            ]
        ];

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, 'Test\User'],
                    ['groups', 'entityClass', $requestType, false, 'Test\Group']
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($entityClass, $value) {
                    return 'normalized::' . $entityClass . '::' . $value;
                }
            );

        $this->context->setRequestData($inputData);
        $this->processor->process($this->context);

        $expectedData = [
            'firstName'           => 'John',
            'lastName'            => 'Doe',
            'toOneRelation'       => [
                'id'    => 'normalized::Test\User::89',
                'class' => 'Test\User'
            ],
            'toManyRelation'      => [
                [
                    'id'    => 'normalized::Test\Group::1',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::Test\Group::2',
                    'class' => 'Test\Group'
                ],
                [
                    'id'    => 'normalized::Test\Group::3',
                    'class' => 'Test\Group'
                ]
            ],
            'emptyToOneRelation'  => [],
            'emptyToManyRelation' => []
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessNoAttributes()
    {
        $inputData = [
            'data' => [
                'relationships' => [
                    'toOneRelation' => [
                        'data' => [
                            'type' => 'users',
                            'id'   => '89'
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($inputData);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['users', 'entityClass', $requestType, false, 'Test\User'],
                ]
            );
        $this->entityIdTransformer->expects($this->any())
            ->method('reverseTransform')
            ->willReturnCallback(
                function ($entityClass, $value) {
                    return (int)$value;
                }
            );

        $this->processor->process($this->context);

        $expectedData = [
            'toOneRelation' => [
                'id'    => '89',
                'class' => 'Test\User'
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }
}
