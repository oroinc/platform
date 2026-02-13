<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Validator;

use Oro\Bundle\EntityExtendBundle\Validator\PersistentValidatorCacheWarmer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PersistentValidatorCacheWarmerTest extends WebTestCase
{
    private PersistentValidatorCacheWarmer $cacheWarmer;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->cacheWarmer = self::getContainer()->get('validator.mapping.cache_warmer');
    }

    public function testServiceIsRegistered(): void
    {
        self::assertInstanceOf(PersistentValidatorCacheWarmer::class, $this->cacheWarmer);
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->cacheWarmer->isOptional());
    }

    public function testWarmUpDoesNotThrowException(): void
    {
        $cacheDir = self::getContainer()->getParameter('kernel.cache_dir');

        $result = $this->cacheWarmer->warmUp($cacheDir);

        self::assertIsArray($result);
    }

    public function testExpressionLanguageServiceIsAvailable(): void
    {
        $expressionLanguage = self::getContainer()->get('validator.expression_language');

        self::assertInstanceOf(ExpressionLanguage::class, $expressionLanguage);
    }

    public function testWarmUpAllowsExpressionParsing(): void
    {
        $cacheDir = self::getContainer()->getParameter('kernel.cache_dir');

        // Warm up validator metadata and expression language cache
        $this->cacheWarmer->warmUp($cacheDir);

        // Verify that after warmup, expressions can still be parsed successfully
        // This ensures the warmup process doesn't break the expression language service
        $expressionLanguage = self::getContainer()->get('validator.expression_language');

        $result = $expressionLanguage->parse(
            'value === null || value.isKit() === false',
            ['value', 'this', 'context']
        );

        self::assertNotNull($result);
    }
}
