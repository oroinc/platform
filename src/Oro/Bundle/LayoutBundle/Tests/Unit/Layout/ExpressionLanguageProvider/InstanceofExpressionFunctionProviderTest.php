<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\ExpressionLanguageProvider;

use Oro\Bundle\LayoutBundle\Layout\ExpressionLanguageProvider\InstanceofExpressionFunctionProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class InstanceofExpressionFunctionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var InstanceofExpressionFunctionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new InstanceofExpressionFunctionProvider();
    }

    public function testGetFunctions(): void
    {
        $functions = $this->provider->getFunctions();
        $this->assertCount(1, $functions);

        /** @var ExpressionFunction $function */
        $function = array_shift($functions);

        $object = InstanceofExpressionFunctionProvider::class;
        $className = ExpressionFunctionProviderInterface::class;

        $this->assertInstanceOf(ExpressionFunction::class, $function);
        $this->assertEquals(
            sprintf("is_a('%s','%s')", $object, $className),
            call_user_func($function->getCompiler(), $object, $className)
        );
        $this->assertFalse(call_user_func($function->getEvaluator(), [], \stdClass::class, $className));
        $this->assertTrue(call_user_func($function->getEvaluator(), [], $object, $className));
    }
}
