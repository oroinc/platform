<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Converter;

use Oro\Component\MessageQueue\Log\Converter\ChainMessageToArrayConverter;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class ChainMessageToArrayConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $converter1;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $converter2;

    /** @var ChainMessageToArrayConverter */
    private $chainConverter;

    protected function setUp()
    {
        $this->converter1 = $this->createMock(MessageToArrayConverterInterface::class);
        $this->converter2 = $this->createMock(MessageToArrayConverterInterface::class);

        $this->chainConverter = new ChainMessageToArrayConverter([$this->converter1, $this->converter2]);
    }

    public function testConvert()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
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
