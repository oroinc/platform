<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Converter;

use Oro\Bundle\MessageQueueBundle\Log\Converter\DbalMessageToArrayConverter;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;

class DbalMessageToArrayConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DbalMessageToArrayConverter */
    private $converter;

    protected function setUp()
    {
        $this->converter = new DbalMessageToArrayConverter();
    }

    public function testConvertRequiredProperties()
    {
        $message = new DbalMessage();
        $message->setMessageId('123');
        $message->setPriority(3);

        self::assertEquals(
            [
                'priority' => 3
            ],
            $this->converter->convert($message)
        );
    }

    public function testConvertAllProperties()
    {
        $message = new DbalMessage();
        $message->setMessageId('123');
        $message->setPriority(3);
        $message->setDelay(100);

        self::assertEquals(
            [
                'priority' => 3,
                'delay'    => 100
            ],
            $this->converter->convert($message)
        );
    }
}
