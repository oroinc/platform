<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\AssertHasResult;

class AssertHasResultTest extends GetListProcessorTestCase
{
    /** @var AssertHasResult */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AssertHasResult();
    }

    public function testProcessOnExpectedResult()
    {
        $this->context->setResult([['key' => 'value']]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Getting a list of entities failed.
     */
    public function testProcessOnExistingResult()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported request.
     */
    public function testProcessOnEmptyQuery()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported query type: stdClass.
     */
    public function testProcessOnWrongQuery()
    {
        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);
    }
}
