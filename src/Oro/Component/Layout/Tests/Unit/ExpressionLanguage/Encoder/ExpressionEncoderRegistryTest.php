<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage\Encoder;

use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderInterface;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;

class ExpressionEncoderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionEncoderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encoder;

    /** @var ExpressionEncoderRegistry */
    private $encoderRegistry;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(ExpressionEncoderInterface::class);

        $this->encoderRegistry = new ExpressionEncoderRegistry(
            ['test' => $this->encoder]
        );
    }

    public function testGetEncoder()
    {
        $this->assertSame($this->encoder, $this->encoderRegistry->get('test'));
    }

    public function testGetEncoderThrowsExceptionIfEncoderDoesNotExist()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The expression encoder for "unknown" formatting was not found.');

        $this->encoderRegistry->get('unknown');
    }
}
