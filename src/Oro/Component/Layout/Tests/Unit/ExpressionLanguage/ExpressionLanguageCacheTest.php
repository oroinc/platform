<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\ExpressionLanguage\ClosureWithExtraParams;
use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCache;
use Psr\Log\LoggerInterface;

class ExpressionLanguageCacheTest extends \PHPUnit\Framework\TestCase
{
    public function testGetClosure()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $cache = new ExpressionLanguageCache(__DIR__ . '/cached_expressions_stub.php', $logger);

        $logger->expects(self::never())
            ->method(self::anything());

        $context = $this->createMock(ContextInterface::class);
        $data = $this->createMock(DataAccessorInterface::class);

        $result = $cache->getClosure('expression_1');
        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals('expression_1_result', $result($context, $data));

        $result = $cache->getClosure('not_cached_expression');
        $this->assertNull($result);

        $result = $cache->getClosure('expression_2');
        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals('expression_2_result', $result($context, $data));
    }

    public function testGetClosureWhenFileNotFound()
    {
        $cacheFilePath = __DIR__ . '/non_existing_file.php';
        $logger = $this->createMock(LoggerInterface::class);
        $cache = new ExpressionLanguageCache($cacheFilePath, $logger);

        $logger->expects(self::once())
            ->method('error')
            ->with(
                'The file with compiled layout expressions does not exist.',
                ['file' => $cacheFilePath]
            );

        $result = $cache->getClosure('expression_1');
        $this->assertNull($result);

        $result = $cache->getClosure('expression_2');
        $this->assertNull($result);
    }

    public function testGetClosureWithExtraParams()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $cache = new ExpressionLanguageCache(__DIR__ . '/cached_expressions_stub.php', $logger);

        $logger->expects(self::never())
            ->method(self::anything());

        $context = $this->createMock(ContextInterface::class);
        $data = $this->createMock(DataAccessorInterface::class);

        $result = $cache->getClosureWithExtraParams('expression_3');
        $this->assertInstanceOf(ClosureWithExtraParams::class, $result);
        $this->assertEquals(['param1'], $result->getExtraParamNames());
        $closure = $result->getClosure();
        $this->assertEquals('expression_3_result', $closure($context, $data, 'param1'));

        $result = $cache->getClosureWithExtraParams('not_cached_expression');
        $this->assertNull($result);

        $result = $cache->getClosureWithExtraParams('expression_4');
        $this->assertInstanceOf(ClosureWithExtraParams::class, $result);
        $this->assertEquals(['param1', 'param2'], $result->getExtraParamNames());
        $closure = $result->getClosure();
        $this->assertEquals('expression_4_result', $closure($context, $data, 'param1', 'param2'));
    }

    public function testGetClosureWithExtraParamsWhenFileNotFound()
    {
        $cacheFilePath = __DIR__ . '/non_existing_file.php';
        $logger = $this->createMock(LoggerInterface::class);
        $cache = new ExpressionLanguageCache($cacheFilePath, $logger);

        $logger->expects(self::once())
            ->method('error')
            ->with(
                'The file with compiled layout expressions does not exist.',
                ['file' => $cacheFilePath]
            );

        $result = $cache->getClosureWithExtraParams('expression_3');
        $this->assertNull($result);

        $result = $cache->getClosureWithExtraParams('expression_4');
        $this->assertNull($result);
    }
}
