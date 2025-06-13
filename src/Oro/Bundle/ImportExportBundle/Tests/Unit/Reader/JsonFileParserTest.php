<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use JsonStreamingParser\Exception\ParsingException;
use Oro\Bundle\ImportExportBundle\Reader\JsonFileParser;
use PHPUnit\Framework\TestCase;

class JsonFileParserTest extends TestCase
{
    private function parse(string $json): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);
        try {
            $parser = new JsonFileParser($stream);
            $result = [];
            while (!$parser->isEof()) {
                $parser->parse();
                if ($parser->hasItem()) {
                    $result[] = $parser->getItem();
                }
            }

            return $result;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse(string $json, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->parse($json));
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseWithEolAlterKeys(string $json, array $expectedResult): void
    {
        $json = str_replace('":', '":' . "\n", $json);
        self::assertSame($expectedResult, $this->parse($json));
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseWithEolBeforeEachObject(string $json, array $expectedResult): void
    {
        $json = str_replace('{', "\n" . '{', $json);
        self::assertSame($expectedResult, $this->parse($json));
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseWithEolAlterEachObject(string $json, array $expectedResult): void
    {
        $json = str_replace('}', '}' . "\n", $json);
        self::assertSame($expectedResult, $this->parse($json));
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseWithEolAlterStartOfEachObject(string $json, array $expectedResult): void
    {
        $json = str_replace('{', '{' . "\n", $json);
        self::assertSame($expectedResult, $this->parse($json));
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseWithEolBeforeEndOfEachObject(string $json, array $expectedResult): void
    {
        $json = str_replace('}', "\n" . '}', $json);
        self::assertSame($expectedResult, $this->parse($json));
    }

    public static function parseDataProvider(): array
    {
        return [
            [
                '[]',
                []
            ],
            [
                '[{"key": null}]',
                [['key' => null]]
            ],
            [
                '[{"key1": "value1", "key2": {"key2_1": null}}]',
                [['key1' => 'value1', 'key2' => ['key2_1' => null]]]
            ],
            [
                '[{"key1": "value1", "key2": [null, {"key2_1": "value2_1_2"}]}]',
                [['key1' => 'value1', 'key2' => [null, ['key2_1' => 'value2_1_2']]]]
            ],
            [
                '[{"key1": "value1", "key2": [{"key2_1": "value2_1_1"}, null]}]',
                [['key1' => 'value1', 'key2' => [['key2_1' => 'value2_1_1'], null]]]
            ],
            [
                '[{"key1": "value1", "key2": [{"key2_1": "value2_1_1"}, null, {"key2_1": "value2_1_3"}]}]',
                [['key1' => 'value1', 'key2' => [['key2_1' => 'value2_1_1'], null, ['key2_1' => 'value2_1_3']]]]
            ],
            [
                '[{"key": "value"}]',
                [['key' => 'value']]
            ],
            [
                '[{"key": "value1"}, {"key": "value2"}]',
                [['key' => 'value1'], ['key' => 'value2']]
            ],
            [
                '['
                . '{"key1": "value1", "key2": {"key2_1": "value2_1"}},'
                . '{"key1": "value2", "key2": {"key2_1": "value2_2"}}'
                . ']',
                [
                    ['key1' => 'value1', 'key2' => ['key2_1' => 'value2_1']],
                    ['key1' => 'value2', 'key2' => ['key2_1' => 'value2_2']]
                ]
            ],
            [
                '['
                . '{"key1": "value1", "key2": [{"key2_1": "value2_1_1"}, {"key2_1": "value2_1_2"}]},'
                . '{"key1": "value2", "key2": [{"key2_1": "value2_2"}]}'
                . ']',
                [
                    ['key1' => 'value1', 'key2' => [['key2_1' => 'value2_1_1'], ['key2_1' => 'value2_1_2']]],
                    ['key1' => 'value2', 'key2' => [['key2_1' => 'value2_2']]]
                ]
            ],
        ];
    }

    /**
     * @dataProvider parseForInvalidJsonDataProvider
     */
    public function testParseForInvalidJson(string $json, string $exceptionMessage): void
    {
        $this->expectException(ParsingException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->parse($json);
    }

    public static function parseForInvalidJsonDataProvider(): array
    {
        return [
            [
                '  null',
                'Parsing error in [1:3]. Document must start with object or array.'
            ],
            [
                '  {}',
                'Parsing error in [1:3]. Document must start with array.'
            ],
            [
                '  {"key": "value"}',
                'Parsing error in [1:3]. Document must start with array.'
            ],
            [
                '[null]',
                'Parsing error in [1:2]. The object cannot bu null.'
            ],
            [
                '[null, {"key": "value"}]',
                'Parsing error in [1:2]. The object cannot bu null.'
            ],
            [
                '[{"key": "value"}, null]',
                'Parsing error in [1:20]. The object cannot bu null.'
            ],
            [
                '[{"key": "value1"}, null, {"key": "value2"}]',
                'Parsing error in [1:21]. The object cannot bu null.'
            ],
        ];
    }
}
