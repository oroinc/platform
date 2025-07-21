<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;
use PHPUnit\Framework\TestCase;

class CallbackMessageBuilderTest extends TestCase
{
    public function testGetMessage(): void
    {
        $builder = new CallbackMessageBuilder(function () {
            return 'test message';
        });
        self::assertEquals('test message', $builder->getMessage());
    }
}
