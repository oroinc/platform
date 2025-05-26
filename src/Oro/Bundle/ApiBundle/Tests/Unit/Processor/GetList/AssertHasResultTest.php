<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\AssertHasResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssertHasResultTest extends GetListProcessorTestCase
{
    private AssertHasResult $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertHasResult();
    }

    public function testProcessOnExpectedResult(): void
    {
        $this->context->setResult([['key' => 'value']]);
        $this->processor->process($this->context);
    }

    public function testProcessOnExistingResult(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Getting a list of entities failed.');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessOnEmptyQuery(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported request.');

        $this->processor->process($this->context);
    }

    public function testProcessOnWrongQuery(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported query type: stdClass.');

        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);
    }
}
