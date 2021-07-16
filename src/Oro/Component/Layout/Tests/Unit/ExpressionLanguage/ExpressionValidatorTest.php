<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ExpressionLanguage\ExpressionValidator;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ExpressionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new ExpressionValidator();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $expression, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException(SyntaxError::class);
            $this->expectExceptionMessage($exception);
        }

        $this->validator->validate($expression);
    }

    /**
     * Note: exception messages must contain `around position` and `for expression` part
     * for correct syntax error exception
     */
    public function validateDataProvider(): array
    {
        return [
            'valid expression' => [
                'expression' => 'data["some_key"].callFunction(a ? b)',
            ],
            'operator collisions' => [
                // {@see https://github.com/symfony/symfony/pull/35707}
                'expression' => 'foo.not in [bar]',
            ],
            'incorrect expression ending' => [
                'expression' => 'data["a"] data["b"]',
                'exception' => 'Unexpected token "name" of value "data" ' .
                    'around position 11 for expression `data["a"] data["b"]`.',
            ],
            'incorrect operator' => [
                'expression' => 'data["some_key"] // 2',
                'exception' => 'Unexpected token "operator" of value "/" ' .
                    'around position 19 for expression `data["some_key"] // 2`.',
            ],
            'incorrect array' => [
                'expression' => '[value1, value2 value3]',
                'exception' => 'An array element must be followed by a comma. ' .
                    'Unexpected token "name" of value "value3" ("punctuation" expected with value ",") ' .
                    'around position 17 for expression `[value1, value2 value3]`.',
            ],
            'incorrect array element' => [
                'expression' => 'data["some_key")',
                'exception' => 'Unclosed "[" around position 4 for expression `data["some_key")`.',
            ],
            'missed array key' => [
                'expression' => 'data[]',
                'exception' => 'Unexpected token "punctuation" of value "]" around position 6 for expression `data[]`.',
            ],
            'missed closing bracket in sub expression' => [
                'expression' => 'data[(key ? key : "default"]',
                'exception' => 'Unclosed "(" around position 5 for expression `data[(key ? key : "default"]`.',
            ],
            'incorrect hash following' => [
                'expression' => '{key: val key2: val2}',
                'exception' => 'A hash value must be followed by a comma. ' .
                    'Unexpected token "name" of value "key2" ("punctuation" expected with value ",") ' .
                    'around position 11 for expression `{key: val key2: val2}`.',
            ],
            'incorrect hash assign' => [
                'expression' => '{key => val}',
                'exception' => 'Unexpected character "=" around position 5 for expression `{key => val}`.',
            ],
            'incorrect array as hash using' => [
                'expression' => '[key: val]',
                'exception' => 'An array element must be followed by a comma. ' .
                    'Unexpected token "punctuation" of value ":" ("punctuation" expected with value ",") ' .
                    'around position 5 for expression `[key: val]`.',
            ],
        ];
    }
}
