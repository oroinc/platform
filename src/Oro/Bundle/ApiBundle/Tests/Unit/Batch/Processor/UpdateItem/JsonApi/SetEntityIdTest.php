<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi\SetEntityId;
use Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\BatchUpdateItemProcessorTestCase;

class SetEntityIdTest extends BatchUpdateItemProcessorTestCase
{
    /** @var SetEntityId */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetEntityId();
    }

    public function testProcessWithoutRequestData()
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData(array $data)
    {
        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function invalidRequestDataProvider(): array
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
                'id' => '123'
            ]
        ];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertEquals('123', $this->context->getId());
    }
}
