<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class EvaluateExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configurationForEvaluateDataProvider
     */
    public function testAssembleWithEvaluateResult($yaml, $context, $expected)
    {
        $language      = new ConfigExpressions();
        $configuration = Yaml::parse(file_get_contents($yaml));

        $this->assertEquals(
            $expected,
            $language->evaluate($configuration, $context)
        );

        $expr                    = $language->getExpression($configuration);
        $normalizedConfiguration = $expr->toArray();
        $this->assertEquals(
            $expected,
            $language->evaluate($normalizedConfiguration, $context)
        );
    }

    public function configurationForEvaluateDataProvider()
    {
        $conditionWithFunc = <<<YAML
"@empty":
    - "@trim": \$name
YAML;

        return [
            [
                $conditionWithFunc,
                $this->createObject(['name' => '  ']),
                true
            ],
            [
                $conditionWithFunc,
                $this->createObject(['name' => ' test ']),
                false
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return ItemStub
     */
    protected function createObject(array $data = [])
    {
        return new ItemStub($data);
    }
}
