<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi\SetEntityClass;
use Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\BatchUpdateItemProcessorTestCase;

class SetEntityClassTest extends BatchUpdateItemProcessorTestCase
{
    /** @var SetEntityClass */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetEntityClass();
    }

    public function testProcessWithoutRequestData()
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getClassName());
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData($data)
    {
        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertNull($this->context->getClassName());
    }

    /**
     * @return array
     */
    public function invalidRequestDataProvider()
    {
        return [
            [[]],
            [['data' => []]]
        ];
    }

    public function testProcessWithValidRequestData()
    {
        $data = [
            'data' => [
                'type' => 'entity'
            ]
        ];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertEquals('entity', $this->context->getClassName());
    }
}
