<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use PHPUnit\Framework\TestCase;

class NormalizeResultContextTest extends TestCase
{
    private NormalizeResultContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new NormalizeResultContext();
    }

    public function testErrors(): void
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

    public function testSoftErrorsHandling(): void
    {
        self::assertFalse($this->context->isSoftErrorsHandling());

        $this->context->setSoftErrorsHandling(true);
        self::assertTrue($this->context->isSoftErrorsHandling());

        $this->context->setSoftErrorsHandling(false);
        self::assertFalse($this->context->isSoftErrorsHandling());
    }
}
