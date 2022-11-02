<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use Oro\Component\Layout\Tests\Unit\ExpressionLanguage\Fixtures\ClassWithConstant;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Filesystem\Filesystem;

class ExpressionLanguageCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    private const CACHE_FILE_PATH = 'cache/file/path';

    /** @var Filesystem|\PHPUnit\Framework\MockObject\MockObject */
    private $fs;

    /** @var BufferingLogger */
    private $logger;

    /** @var ExpressionLanguageCacheWarmer */
    private $warmer;

    protected function setUp(): void
    {
        $this->fs = $this->createMock(Filesystem::class);
        $this->logger = new BufferingLogger();

        $this->warmer = new ExpressionLanguageCacheWarmer(
            new ExpressionLanguage(),
            $this->fs,
            self::CACHE_FILE_PATH,
            $this->logger
        );
    }

    private function assertLogMessages(array $expected, bool $withMessage = false): void
    {
        $logs = $this->logger->cleanLogs();
        if (!$withMessage) {
            $logs = array_map(function (array $record) {
                unset($record[2]['message']);

                return $record;
            }, $logs);
        }

        self::assertEquals($expected, $logs);
    }

    public function testCollectAndWrite(): void
    {
        $expectedPhpFileContent = <<<'PHP_FILE'
<?php return [
'closures' => [
    'data["a"].b() && context["c"]' => static function ($context, $data) { return ($data["a"]->b() && $context["c"]); },
    'data["foo"].bar()' => static function ($context, $data) { return $data["foo"]->bar(); },
],
'closuresWithExtraParams' => [
    'data["a"].b(args)' => [static function ($context, $data, $args) { return $data["a"]->b($args); }, ['args']],
]
];
PHP_FILE;
        $this->fs->expects($this->once())
            ->method('dumpFile')
            ->with(self::CACHE_FILE_PATH, $expectedPhpFileContent);

        $this->warmer->collect('context["test"]'); // works only with context, should be skipped
        $this->warmer->collect('"expression without variables"'); // no variables, should be skipped
        $this->warmer->collect('data["a"].b() && context["c"]');
        $this->warmer->collect('data["foo"].bar()');
        $this->warmer->collect('data["foo"].bar()'); // duplicate should be skipped
        $this->warmer->collect('data["a"].b(args)'); // extra variable
        $this->warmer->collect('data["a"]->b(args)'); // invalid expression
        $this->warmer->collect('constant("' . ClassWithConstant::class . '::TEST")'); // constant, should be skipped

        $this->warmer->write();

        $this->assertLogMessages([
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => 'context["test"]']
            ],
            [
                'info',
                'There is no need to cache the layout expression.',
                ['expression' => 'context["test"]']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => '"expression without variables"']
            ],
            [
                'info',
                'There is no need to cache the layout expression.',
                ['expression' => '"expression without variables"']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => 'data["a"].b() && context["c"]']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => 'data["foo"].bar()']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => 'data["a"].b(args)']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' =>'data["a"]->b(args)']
            ],
            [
                'error',
                'The layout expression cannot be cached because it cannot be compiled.',
                ['expression' => 'data["a"]->b(args)']
            ],
            [
                'debug',
                'Compile the layout expression.',
                ['expression' =>'constant("' . ClassWithConstant::class . '::TEST")']
            ],
            [
                'info',
                'There is no need to cache the layout expression.',
                ['expression' => 'constant("' . ClassWithConstant::class . '::TEST")']
            ],
        ]);
    }

    /**
     * @dataProvider expressionsWithExtraVariablesDataProvider
     */
    public function testExpressionsWithExtraVariables(
        string $expression,
        string $compiledExpression,
        array $extraParamNames
    ): void {
        $expectedPhpFileContent = "<?php return [\n'closures' => [\n],\n'closuresWithExtraParams' => [\n    '"
            . $expression
            . '\' => [static function ($context, $data, $'
            . implode(', $', $extraParamNames)
            . ') { '
            . $compiledExpression
            . ' }, [\''
            . implode('\', \'', $extraParamNames)
            . "']],\n]\n];";
        $this->fs->expects($this->once())
            ->method('dumpFile')
            ->with(self::CACHE_FILE_PATH, $expectedPhpFileContent);

        $this->warmer->collect($expression);
        $this->warmer->write();

        $this->assertLogMessages([
            [
                'debug',
                'Compile the layout expression.',
                ['expression' => $expression]
            ]
        ]);
    }

    public function expressionsWithExtraVariablesDataProvider(): array
    {
        return [
            [
                'var',
                'return $var;',
                ['var']
            ],
            [
                'var["test"]',
                'return $var["test"];',
                ['var']
            ],
            [
                'var.foo()',
                'return $var->foo();',
                ['var']
            ],
            [
                'var && data["test"]',
                'return ($var && $data["test"]);',
                ['var']
            ],
            [
                'data["test"] && var',
                'return ($data["test"] && $var);',
                ['var']
            ],
            [
                'var1 && var2',
                'return ($var1 && $var2);',
                ['var1', 'var2']
            ],
            [
                'context["var1"] && var1.foo(var2)',
                'return ($context["var1"] && $var1->foo($var2));',
                ['var1', 'var2']
            ],
        ];
    }
}
