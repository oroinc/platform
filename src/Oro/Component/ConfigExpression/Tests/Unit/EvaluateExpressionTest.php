<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\Yaml\Yaml;

class EvaluateExpressionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider configurationForEvaluateDataProvider
     */
    public function testAssembleWithEvaluateResult(string $yaml, ItemStub $context, bool $expected)
    {
        $language = new ConfigExpressions();
        $configuration = Yaml::parse($yaml);

        $this->assertEquals(
            $expected,
            $language->evaluate($configuration, $context)
        );

        $expr = $language->getExpression($configuration);
        $normalizedConfiguration = $expr->toArray();
        $this->assertEquals(
            $expected,
            $language->evaluate($normalizedConfiguration, $context)
        );
    }

    public function configurationForEvaluateDataProvider(): array
    {
        $conditionWithFunc = <<<YAML
"@empty":
    - "@trim": \$name
YAML;

        return [
            [
                $conditionWithFunc,
                new ItemStub(['name' => '  ']),
                true
            ],
            [
                $conditionWithFunc,
                new ItemStub(['name' => ' test ']),
                false
            ]
        ];
    }
}
