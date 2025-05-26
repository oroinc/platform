<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocAnnotationParser;
use PHPUnit\Framework\TestCase;

class ApiDocAnnotationParserTest extends TestCase
{
    private ApiDocAnnotationParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new ApiDocAnnotationParser();
    }

    public function testSupportsWithoutFields(): void
    {
        $item = [];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithUnexpectedTypeOfFields(): void
    {
        $item = [
            'fields' => 'a string'
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithFieldsAsArray(): void
    {
        $item = [
            'fields' => []
        ];

        self::assertTrue($this->parser->supports($item));
    }

    public function testParseFieldWithProperties(): void
    {
        $item = [
            'fields' => [
                ['name' => 'field1', 'key' => 'value']
            ]
        ];

        self::assertEquals(
            [
                'field1' => ['key' => 'value', 'required' => false]
            ],
            $this->parser->parse($item)
        );
    }

    public function testParseFieldWithoutRequiredProperty(): void
    {
        $item = [
            'fields' => [
                ['name' => 'field1']
            ]
        ];

        self::assertEquals(
            [
                'field1' => ['required' => false]
            ],
            $this->parser->parse($item)
        );
    }

    public function testParseFieldWithRequiredProperty(): void
    {
        $item = [
            'fields' => [
                ['name' => 'field1', 'required' => true]
            ]
        ];

        self::assertEquals(
            [
                'field1' => ['required' => true]
            ],
            $this->parser->parse($item)
        );
    }
}
