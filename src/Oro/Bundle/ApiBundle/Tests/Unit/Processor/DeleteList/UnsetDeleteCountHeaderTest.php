<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\UnsetDeleteCountHeader;

class UnsetTotalCountHeaderTest extends DeleteListProcessorTestCase
{
    /** @var UnsetDeleteCountHeader */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new UnsetDeleteCountHeader();
    }

    public function testProcessWithoutRequestHeader()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->getResponseHeaders()->has('X-Include-Delete-Count'));
    }

    public function testProcessWithoutErrors()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['deleteCount']);
        $this->context->getResponseHeaders()->set('X-Include-Delete-Count', 77);

        $this->processor->process($this->context);

        $this->assertEquals(77, $this->context->getResponseHeaders()->get('X-Include-Delete-Count'));
    }

    public function testProcessWithError()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['deleteCount']);
        $this->context->getResponseHeaders()->set('X-Include-Delete-Count', 77);
        $this->context->setResult(
            [
                'errors' => [
                    'code' => 500,
                    'detail' => 'The record was not deleted'
                ]
            ]
        );

        $this->assertEquals(77, $this->context->getResponseHeaders()->get('X-Include-Delete-Count'));

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResponseHeaders()->get('X-Include-Delete-Count'));
    }
}
