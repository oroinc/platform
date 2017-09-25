<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Converter;

use Oro\Bundle\MessageQueueBundle\Log\Converter\ChainMessageToArrayConverter;
use Oro\Bundle\MessageQueueBundle\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class ChainMessageToArrayConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $converter1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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
