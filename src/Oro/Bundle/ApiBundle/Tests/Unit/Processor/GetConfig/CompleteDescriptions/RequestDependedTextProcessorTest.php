<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\RequestDependedTextProcessor;
use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestDependedTextProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestDependedTextProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new RequestDependedTextProcessor();
    }

    /**
     * @dataProvider validTextExpressionProvider
     */
    public function testProcess(string $text, string $expected)
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(
            $expected,
            $this->processor->process($text, $requestType)
        );
    }

    public function validTextExpressionProvider(): array
    {
        return [
            ['', ''],
            ['Text', 'Text'],
            ['{@request:json_api}JSON API{@/request}', 'JSON API'],
            ['{@request:json_api}REST{@/request}', 'REST'],
            ['{@request:another}Another{@/request}', ''],
            ['{@request:json_api&rest}JSON API & REST{@/request}', 'JSON API & REST'],
            ['{@request:json_api&!rest}JSON API & !REST{@/request}', ''],
            ['{@request:json_api&another}JSON API & Another{@/request}', ''],
            ['{@request:json_api&!another}JSON API & !Another{@/request}', 'JSON API & !Another'],
            ['{@request:json_api|rest}JSON API | REST{@/request}', 'JSON API | REST'],
            ['{@request:json_api|!rest}JSON API | !REST{@/request}', 'JSON API | !REST'],
            ['{@request:json_api|another}JSON API | Another{@/request}', 'JSON API | Another'],
            ['{@request:json_api|!another}JSON API | !Another{@/request}', 'JSON API | !Another'],
            ['Hello {@request:json_api}JSON API{@/request}!', 'Hello JSON API!'],
            ['{@request:rest}REST{@/request} {@request:json_api}JSON API{@/request}', 'REST JSON API']
        ];
    }

    /**
     * @dataProvider invalidTextExpressionProvider
     */
    public function testProcessForInvalidExpression(string $text)
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(
            $text,
            $this->processor->process($text, $requestType)
        );
    }

    public function invalidTextExpressionProvider(): array
    {
        return [
            ['{@request:}JSON API{@/request}'],
            ['{@request}JSON API{@/request}'],
            ['{@requestJSON API{@/request}'],
            ['{@request:json_api}JSON API'],
            ['{@request:json_api}JSON API{@request}'],
            ['{@request:json_api}JSON API{@/request'],
            ['{@request:json_api}JSON API{/@request}'],
            ['{@request:json_api}JSON API@/request}']
        ];
    }
}
