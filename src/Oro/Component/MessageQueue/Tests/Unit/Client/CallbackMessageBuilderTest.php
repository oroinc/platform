<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;

class CallbackMessageBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMessage()
    {
        $builder = new CallbackMessageBuilder(function () {
            return 'test message';
        });
        self::assertEquals('test message', $builder->getMessage());
    }
}
