<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCache;
use PHPUnit\Framework\TestCase;

class ExpressionLanguageCacheTest extends TestCase
{
    public function testGetClosure()
    {
        $context = $this->createMock(ContextInterface::class);
        $data = $this->createMock(DataAccessorInterface::class);
        $cache = new ExpressionLanguageCache(__DIR__.'/cached_expressions_stub.php');

        $result = $cache->getClosure('expression_1');
        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals('expression_1_result', $result($context, $data));

        $result = $cache->getClosure('not_cached_expression');
        $this->assertNull($result);

        $result = $cache->getClosure('expression_2');
        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals('expression_2_result', $result($context, $data));
    }

    public function testGetClosureFileNotFound()
    {
        $cache = new ExpressionLanguageCache(__DIR__.'/non_existing_file.php');

        $result = $cache->getClosure('expression_1');
        $this->assertNull($result);

        $result = $cache->getClosure('expression_2');
        $this->assertNull($result);
    }
}
