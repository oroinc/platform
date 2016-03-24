<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeleteCountHeader;

class SetDeleteCountHeaderTest extends DeleteListProcessorTestCase
{
    /** @var SetDeleteCountHeader */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDeleteCountHeader();
    }

    public function testProcessWithoutRequestHeader()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->getResponseHeaders()->has('X-Include-Delete-Count'));
    }

    public function testProcessOnExistingHeader()
    {
        $this->context->getResponseHeaders()->set('X-Include-Delete-Count', 77);
        $this->processor->process($this->context);

        $this->assertEquals(77, $this->context->getResponseHeaders()->get('X-Include-Delete-Count'));
    }

    public function testProcessWithContextResult()
    {
        $testCount = 10;
        $result = array_fill(0, $testCount, new \stdClass());

        $this->context->setResult($result);

        $this->context->getRequestHeaders()->set('X-Include', ['deleteCount']);
        $this->processor->process($this->context);

        $this->assertEquals($testCount, $this->context->getResponseHeaders()->get('X-Include-Delete-Count'));
    }

    public function testProcessWithNullResult()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['deleteCount']);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getResponseHeaders()->get('X-Include-Delete-Count'));
    }
}
