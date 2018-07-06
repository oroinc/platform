<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocAnnotationParser;

class ApiDocAnnotationParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var ApiDocAnnotationParser */
    private $parser;

    protected function setUp()
    {
        $this->parser = new ApiDocAnnotationParser();
    }

    public function testSupportsWithoutFields()
    {
        $item = [];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithUnexpectedTypeOfFields()
    {
        $item = [
            'fields' => 'a string'
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithFieldsAsArray()
    {
        $item = [
            'fields' => []
        ];

        self::assertTrue($this->parser->supports($item));
    }

    public function testParseFieldWithProperties()
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

    public function testParseFieldWithoutRequiredProperty()
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

    public function testParseFieldWithRequiredProperty()
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
