<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestDependedTextProcessor;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class RequestDependedTextProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestDependedTextProcessor */
    private $processor;

    protected function setUp()
    {
        $this->processor = new RequestDependedTextProcessor(new RequestExpressionMatcher());
    }

    /**
     * @dataProvider validTextExpressionProvider
     */
    public function testProcess($text, $expected)
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(
            $expected,
            $this->processor->process($text, $requestType)
        );
    }

    public function validTextExpressionProvider()
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
    public function testProcessForInvalidExpression($text)
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(
            $text,
            $this->processor->process($text, $requestType)
        );
    }

    public function invalidTextExpressionProvider()
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
