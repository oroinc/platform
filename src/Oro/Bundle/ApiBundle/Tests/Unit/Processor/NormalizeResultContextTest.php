<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;

class NormalizeResultContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizeResultContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new NormalizeResultContext();
    }

    public function testErrors()
    {
        $this->assertFalse($this->context->hasErrors());
        $this->assertSame([], $this->context->getErrors());

        $this->context->addError(new Error());
        $this->assertTrue($this->context->hasErrors());
        $this->assertCount(1, $this->context->getErrors());

        $this->context->resetErrors();
        $this->assertFalse($this->context->hasErrors());
        $this->assertSame([], $this->context->getErrors());
    }

    public function testSoftErrorsHandling()
    {
        $this->assertFalse($this->context->isSoftErrorsHandling());
        $this->assertFalse($this->context->has(NormalizeResultContext::SOFT_ERRORS_HANDLING));

        $this->context->setSoftErrorsHandling(true);
        $this->assertTrue($this->context->isSoftErrorsHandling());
        $this->assertTrue($this->context->has(NormalizeResultContext::SOFT_ERRORS_HANDLING));
        $this->assertTrue($this->context->get(NormalizeResultContext::SOFT_ERRORS_HANDLING));

        $this->context->setSoftErrorsHandling(false);
        $this->assertFalse($this->context->isSoftErrorsHandling());
        $this->assertFalse($this->context->has(NormalizeResultContext::SOFT_ERRORS_HANDLING));
    }
}
