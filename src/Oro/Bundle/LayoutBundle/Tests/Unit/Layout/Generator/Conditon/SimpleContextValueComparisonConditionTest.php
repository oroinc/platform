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
        $visitor   = $this->setUpVisitor($oldMethodBody);

        $condition->visit($visitor);

        /** @var PhpMethod[] $methods */
        $methods = $visitor->getClass()->getMethods();
        $method  = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];
        $this->assertSame($expectedMethodBody, $method->getBody()->getContent());
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
if (\$item->getContext()->has('valueToCompare') && \$item->getContext()->get('valueToCompare') === 'my_value') {
    echo 123;
}
CONTENT
            ],
            'neq condition with array value' => [
                '$oldMethodBody'      => 'echo 123;',
                '$value'              => ['testValue', 'testValue2'],
                '$condition'          => '!==',
                '$expectedMethodBody' => <<<CONTENT
if (\$item->getContext()->has('valueToCompare') && \$item->getContext()->get('valueToCompare') !== array (
  0 => 'testValue',
  1 => 'testValue2',
)) {
    echo 123;
}
CONTENT
            ]
        ];
    }

    /**
     * @param string $body
     *
     * @return VisitContext
     */
    protected function setUpVisitor($body)
    {
        $phpClass = PhpClass::create(uniqid('LayoutUpdateClass'));

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR));
        $method->addParameter(PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM));
        $method->setBody($body);
        $phpClass->setMethod($method);

        return new VisitContext($phpClass);
    }
}
