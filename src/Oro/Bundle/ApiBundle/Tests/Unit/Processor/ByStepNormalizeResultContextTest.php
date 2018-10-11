<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;

class ByStepNormalizeResultContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var ByStepNormalizeResultContext */
    private $context;

    protected function setUp()
    {
        $this->context = new ByStepNormalizeResultContext();
    }

    public function testFailedGroup()
    {
        self::assertNull($this->context->getFailedGroup());

        $this->context->setFailedGroup('test');
        self::assertEquals('test', $this->context->getFailedGroup());
        self::assertEquals('test', $this->context->get('failedGroup'));

        $this->context->setFailedGroup(null);
        self::assertNull($this->context->getFailedGroup());
        self::assertFalse($this->context->has('failedGroup'));
    }
}
