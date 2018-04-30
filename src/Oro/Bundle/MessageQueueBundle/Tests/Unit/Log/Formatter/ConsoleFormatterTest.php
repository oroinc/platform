<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Formatter;

use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Client\Config;

class ConsoleFormatterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('Should be fixed in scope of BAP-16989');
    }

    public function testDefaultDataMap()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => [
                'processor'          => 'TestProcessor',
                'message_body'       => 'message body',
                'message_properties' => [
                    Config::PARAMETER_TOPIC_NAME => 'test topic'
                ]
            ]
        ];
        $formatter = new ConsoleFormatter();

        self::assertEquals(
            'DEBUG: test message {"processor":"TestProcessor","topic":"test topic","message":"message body"} ' . "\n",
            $formatter->format($record)
        );
    }

    public function testEmptyDataMap()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => [
                'processor'    => 'TestProcessor',
                'message_body' => 'message body'
            ]
        ];
        $formatter = new ConsoleFormatter([]);

        self::assertEquals(
            'DEBUG: test message  ' . "\n",
            $formatter->format($record)
        );
    }

    public function testCustomDataMap()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => [
                'processor' => 'TestProcessor',
                'message'   => [
                    'body' => 'message body'
                ]
            ]
        ];
        $formatter = new ConsoleFormatter([
            'message' => ['extra', 'message', 'body']
        ]);

        self::assertEquals(
            'DEBUG: test message {"message":"message body"} ' . "\n",
            $formatter->format($record)
        );
    }

    public function testDataValuesNotExist()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => []
        ];
        $formatter = new ConsoleFormatter();

        self::assertEquals(
            'DEBUG: test message  ' . "\n",
            $formatter->format($record)
        );
    }

    public function testDataValueWhenContainerIsNotArray()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => [
                'message' => 'test message'
            ]
        ];
        $formatter = new ConsoleFormatter([
            'message' => ['extra', 'message', 'body']
        ]);

        self::assertEquals(
            'DEBUG: test message  ' . "\n",
            $formatter->format($record)
        );
    }

    public function testWithContext()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [
                'key' => 'value'
            ],
            'extra'      => [
                'processor'    => 'TestProcessor',
                'message_body' => 'message body'
            ]
        ];
        $formatter = new ConsoleFormatter();

        self::assertEquals(
            'DEBUG: test message {"processor":"TestProcessor","message":"message body"} {"key":"value"}' . "\n",
            $formatter->format($record)
        );
    }

    public function testPrettyFormatting()
    {
        $record = [
            'message'    => 'test message',
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [
                'key'         => 'value',
                'jsonString1' => '{"class":"Test\Class","encodedjsonString":"{\"Test\\\\Class\"}"}',
                'jsonString2' => '{"class":"Test\Another\Class"}'
            ],
            'extra'      => []
        ];
        $formatter = new ConsoleFormatter();

        self::assertEquals(
            'DEBUG: test message  {'
            . '"key":"value",'
            . '"jsonString1":{"class":"Test\Class","encodedjsonString":"{\"Test\Class\"}"},'
            . '"jsonString2":{"class":"Test\Another\Class"}'
            . '}' . "\n",
            $formatter->format($record)
        );
    }

    public function testLineBreaks()
    {
        $record = [
            'message'    => "test\nmessage",
            'level'      => 100,
            'level_name' => 'DEBUG',
            'context'    => [],
            'extra'      => []
        ];
        $formatter = new ConsoleFormatter();

        self::assertEquals(
            "DEBUG: test\nmessage  \n",
            $formatter->format($record)
        );
    }
}
