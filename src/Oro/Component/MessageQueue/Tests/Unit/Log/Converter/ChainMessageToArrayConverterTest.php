<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Converter;

use Oro\Component\MessageQueue\Log\Converter\ChainMessageToArrayConverter;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainMessageToArrayConverterTest extends TestCase
{
    private MessageToArrayConverterInterface&MockObject $converter1;
    private MessageToArrayConverterInterface&MockObject $converter2;
    private ChainMessageToArrayConverter $chainConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter1 = $this->createMock(MessageToArrayConverterInterface::class);
        $this->converter2 = $this->createMock(MessageToArrayConverterInterface::class);

        $this->chainConverter = new ChainMessageToArrayConverter([$this->converter1, $this->converter2]);
    }

    public function testConvert(): void
    {
        $message = $this->createMock(MessageInterface::class);

        $this->converter1->expects(self::once())
            ->method('convert')
            ->with(self::identicalTo($message))
            ->willReturn(['prop1' => 'val1', 'prop2' => 'val2']);
        $this->converter2->expects(self::once())
            ->method('convert')
            ->with(self::identicalTo($message))
            ->willReturn(['prop2' => 'val22', 'prop3' => 'val3']);

        self::assertEquals(
            ['prop1' => 'val1', 'prop2' => 'val22', 'prop3' => 'val3'],
            $this->chainConverter->convert($message)
        );
    }
}
