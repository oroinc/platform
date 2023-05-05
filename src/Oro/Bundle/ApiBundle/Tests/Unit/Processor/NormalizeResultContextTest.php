<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;

class NormalizeResultContextTest extends \PHPUnit\Framework\TestCase
{
    private NormalizeResultContext $context;

    protected function setUp(): void
    {
        $this->context = new NormalizeResultContext();
    }

    public function testErrors()
    {
        self::assertFalse($this->context->hasErrors());
        self::assertSame([], $this->context->getErrors());

        $this->context->addError(new Error());
        self::assertTrue($this->context->hasErrors());
        self::assertCount(1, $this->context->getErrors());

        $this->context->resetErrors();
        self::assertFalse($this->context->hasErrors());
        self::assertSame([], $this->context->getErrors());
    }

    public function testSoftErrorsHandling()
    {
        self::assertFalse($this->context->isSoftErrorsHandling());

        $this->context->setSoftErrorsHandling(true);
        self::assertTrue($this->context->isSoftErrorsHandling());

        $this->context->setSoftErrorsHandling(false);
        self::assertFalse($this->context->isSoftErrorsHandling());
    }
}
