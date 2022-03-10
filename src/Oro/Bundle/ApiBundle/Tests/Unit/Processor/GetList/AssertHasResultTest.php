<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\AssertHasResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssertHasResultTest extends GetListProcessorTestCase
{
    /** @var AssertHasResult */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertHasResult();
    }

    public function testProcessOnExpectedResult()
    {
        $this->context->setResult([['key' => 'value']]);
        $this->processor->process($this->context);
    }

    public function testProcessOnExistingResult()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Getting a list of entities failed.');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessOnEmptyQuery()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported request.');

        $this->processor->process($this->context);
    }

    public function testProcessOnWrongQuery()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported query type: stdClass.');

        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);
    }
}
