<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueOptionValueParser;
use PHPUnit\Framework\TestCase;

final class QueueOptionValueParserTest extends TestCase
{
    private QueueOptionValueParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new QueueOptionValueParser();
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse(string $input, string $expectedName, array $expectedQueueSettings): void
    {
        $result = $this->parser->parse($input);

        self::assertSame($expectedName, $result['name']);
        self::assertSame($expectedQueueSettings, $result['queueSettings']);
    }

    public static function parseDataProvider(): iterable
    {
        yield 'plain queue name without key-value pairs' => [
            'oro.default',
            'oro.default',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'name and processor key-value pairs' => [
            'name=oro.index,processor=my_proc',
            'oro.index',
            [QueueConsumer::PROCESSOR => 'my_proc'],
        ];

        yield 'name key only without processor' => [
            'name=oro.index',
            'oro.index',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'name, processor, and extra key-value pair' => [
            'name=oro.index,processor=my_proc,weight=10',
            'oro.index',
            [QueueConsumer::PROCESSOR => 'my_proc', 'weight' => '10'],
        ];

        yield 'key-value pairs without name key falls back to plain mode' => [
            'processor=my_proc',
            'processor=my_proc',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'plain queue name with surrounding whitespace is trimmed' => [
            ' oro.default ',
            'oro.default',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'key-value pairs with surrounding whitespace are trimmed' => [
            ' name = oro.index , processor = my_proc ',
            'oro.index',
            [QueueConsumer::PROCESSOR => 'my_proc'],
        ];

        yield 'mixed key-value and plain word falls back to plain mode' => [
            'name=oro.index,justaplainword',
            'name=oro.index,justaplainword',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'multiple key-value pairs without name key falls back to plain mode' => [
            'processor=my_proc,weight=5',
            'processor=my_proc,weight=5',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'empty string returns empty name and empty processor' => [
            '',
            '',
            [QueueConsumer::PROCESSOR => ''],
        ];

        yield 'value containing equals sign is preserved in key-value mode' => [
            'name=oro.index,meta=key=value',
            'oro.index',
            [QueueConsumer::PROCESSOR => '', 'meta' => 'key=value'],
        ];
    }
}
