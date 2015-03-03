<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator\Condition;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\SimpleContextValueComparisonCondition;

class SimpleContextValueComparisonConditionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider conditionDataProvider
     *
     * @param string $oldMethodBody
     * @param mixed  $value
     * @param string $condition
     * @param string $expectedMethodBody
     */
    public function testVisit($oldMethodBody, $value, $condition, $expectedMethodBody)
    {
        $condition = new SimpleContextValueComparisonCondition('valueToCompare', $condition, $value);

        $phpClass = PhpClass::create('LayoutUpdateClass');
        $visitContext = new VisitContext($phpClass);

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR));
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM));

        $condition->startVisit($visitContext);
        $visitContext->getWriter()->writeln($oldMethodBody);
        $condition->endVisit($visitContext);

        $method->setBody($visitContext->getWriter()->getContent());
        $phpClass->setMethod($method);

        $this->assertSame($expectedMethodBody, $method->getBody());
    }

    /**
     * @return array
     */
    public function conditionDataProvider()
    {
        return [
            'simple eq condition'            => [
                '$oldMethodBody'      => 'echo 123;',
                '$value'              => 'my_value',
                '$condition'          => '===',
                '$expectedMethodBody' => <<<CONTENT
if (
    \$item->getContext()->getOr('valueToCompare') === 'my_value'
) {
    echo 123;
}

CONTENT
            ],
            'neq condition with array value' => [
                '$oldMethodBody'      => 'echo 123;',
                '$value'              => ['testValue', 'testValue2'],
                '$condition'          => '!==',
                '$expectedMethodBody' => <<<CONTENT
if (
    \$item->getContext()->getOr('valueToCompare') !== array (
      0 => 'testValue',
      1 => 'testValue2',
    )
) {
    echo 123;
}

CONTENT
            ]
        ];
    }
}
