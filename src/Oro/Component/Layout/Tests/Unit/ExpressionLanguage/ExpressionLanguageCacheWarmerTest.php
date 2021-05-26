<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Filesystem\Filesystem;

class ExpressionLanguageCacheWarmerTest extends TestCase
{
    public function testCollectAndWrite()
    {
        $expressionLanguage = new ExpressionLanguage();
        $fs = $this->createMock(Filesystem::class);
        $cacheFilePath = 'cache/file/path';
        $expectedPhpFileContent = <<<'PHP_FILE'
<?php return [
    'data["a"].b() && context["c"]' => static function ($context, $data) { return ($data["a"]->b() && $context["c"]); },
    'data["foo"].bar()' => static function ($context, $data) { return $data["foo"]->bar(); },
];
PHP_FILE;
        $fs->expects($this->once())
            ->method('dumpFile')
            ->with($cacheFilePath, $expectedPhpFileContent);
        $warmer = new ExpressionLanguageCacheWarmer($expressionLanguage, $fs, $cacheFilePath);

        $warmer->collect('context["test"]'); // works only with context, should be skipped
        $warmer->collect('"expression without variables"'); // no variables, should be skipped
        $warmer->collect('data["a"].b() && context["c"]');
        $warmer->collect('data["foo"].bar()');
        $warmer->collect('data["foo"].bar()'); // duplicate should be skipped
        $warmer->collect('customVariable && data["test"]'); // invalid variable should be skipped

        $warmer->write();
    }
}
