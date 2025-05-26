<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\CheckNoHateoasLinks;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class CheckNoHateoasLinksTest extends GetListProcessorTestCase
{
    private CheckNoHateoasLinks $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->context->setHateoas(true);
        $this->processor = new CheckNoHateoasLinks();
    }

    public function testProcessWithEmptyRequestHeader(): void
    {
        $this->processor->process($this->context);

        self::assertTrue($this->context->isHateoasEnabled());
    }

    public function testProcessWithNoHateoasInRequestHeader(): void
    {
        $this->context->getRequestHeaders()->set('X-Include', ['noHateoas']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->isHateoasEnabled());
    }

    public function testProcessWithSeveralRequestHeadersAndNoHateoasExists(): void
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount', 'noHateoas']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->isHateoasEnabled());
    }

    public function testProcessWithRequestHeadersButNoHateoasDoesNotExists(): void
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);

        self::assertTrue($this->context->isHateoasEnabled());
    }
}
