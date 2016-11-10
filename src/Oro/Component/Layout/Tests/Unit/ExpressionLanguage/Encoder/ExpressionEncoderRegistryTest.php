<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage\Encoder;

use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderInterface;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;

class ExpressionEncoderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionEncoderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var ExpressionEncoderRegistry */
    protected $encoderRegistry;

    protected function setUp()
    {
        $this->encoder = $this->getMock(ExpressionEncoderInterface::class, [], [], '', false);
        $this->encoderRegistry = new ExpressionEncoderRegistry(
            ['test' => $this->encoder]
        );
    }

    public function testGetEncoder()
    {
        $this->assertSame($this->encoder, $this->encoderRegistry->get('test'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The expression encoder for "unknown" formatting was not found.
     */
    public function testGetEncoderThrowsExceptionIfEncoderDoesNotExist()
    {
        $this->encoderRegistry->get('unknown');
    }
}
