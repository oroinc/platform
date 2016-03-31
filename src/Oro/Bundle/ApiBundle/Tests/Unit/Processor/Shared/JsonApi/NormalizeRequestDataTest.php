<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\SingleItemUpdateContextTestCase;

class NormalizeRequestDataTest extends SingleItemUpdateContextTestCase
{
    /** @var NormalizeRequestData */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new NormalizeRequestData();
    }

    public function testProcessOnValidatedData()
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
                'attributes' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ]
            ],
            'relationships' => [
                'relationEntity' => [
                    'data' => [
                        'type' => 'relationEntity',
                        'id' => '89'
                    ]
                ]
            ]
        ];

        $this->context->setRequestData($inputData);
        $this->processor->process($this->context);
        $expectedData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'relationEntity' => '89'
        ];
        $this->assertEquals($expectedData, $this->context->getRequestData());
    }
}
