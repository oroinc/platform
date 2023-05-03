<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;

class ByStepNormalizeResultContextTest extends \PHPUnit\Framework\TestCase
{
    private ByStepNormalizeResultContext $context;

    protected function setUp(): void
    {
        $this->context = new ByStepNormalizeResultContext();
    }

    public function testSourceGroup()
    {
        self::assertNull($this->context->getSourceGroup());

        $this->context->setSourceGroup('test');
        self::assertEquals('test', $this->context->getSourceGroup());
        self::assertEquals('test', $this->context->get('sourceGroup'));

        $this->context->setSourceGroup('');
        self::assertSame('', $this->context->getSourceGroup());
        self::assertSame('', $this->context->get('sourceGroup'));

        $this->context->setSourceGroup(null);
        self::assertNull($this->context->getSourceGroup());
        self::assertFalse($this->context->has('sourceGroup'));
    }

    public function testFailedGroup()
    {
        self::assertNull($this->context->getFailedGroup());

        $this->context->setFailedGroup('test');
        self::assertEquals('test', $this->context->getFailedGroup());
        self::assertEquals('test', $this->context->get('failedGroup'));

        $this->context->setFailedGroup('');
        self::assertSame('', $this->context->getFailedGroup());
        self::assertSame('', $this->context->get('failedGroup'));

        $this->context->setFailedGroup(null);
        self::assertNull($this->context->getFailedGroup());
        self::assertFalse($this->context->has('failedGroup'));
    }
}
