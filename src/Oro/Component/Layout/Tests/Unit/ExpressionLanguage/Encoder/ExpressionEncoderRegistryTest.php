<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage\Encoder;

use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderInterface;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExpressionEncoderRegistryTest extends TestCase
{
    private ExpressionEncoderInterface&MockObject $encoder;
    private ExpressionEncoderRegistry $encoderRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->encoder = $this->createMock(ExpressionEncoderInterface::class);

        $this->encoderRegistry = new ExpressionEncoderRegistry(
            ['test' => $this->encoder]
        );
    }

    public function testGetEncoder(): void
    {
        $this->assertSame($this->encoder, $this->encoderRegistry->get('test'));
    }

    public function testGetEncoderThrowsExceptionIfEncoderDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The expression encoder for "unknown" formatting was not found.');

        $this->encoderRegistry->get('unknown');
    }
}
