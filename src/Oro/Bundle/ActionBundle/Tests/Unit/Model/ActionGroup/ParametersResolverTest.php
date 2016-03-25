<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\Parameter;

class ParametersResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var ParametersResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ParametersResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     * @param ActionData $data
     * @param array $parameters
     * @param ActionData $expected
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testResolveOk(ActionData $data, array $parameters, ActionData $expected)
    {
        $mockActionGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $mockActionGroup->expects($this->once())->method('getParameters')->willReturn($parameters);


        $this->resolver->resolve($data, $mockActionGroup);

        $this->assertEquals($data, $expected);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        $stringRequiredParam = new Parameter('param1');
        $stringRequiredParam->setType('string');

        $mixedRequiredParam = new Parameter('param2');

        $optionalParam = new Parameter('param3');
        $optionalParam->setDefault('default value');

        $expectedModifiedData = new ActionData(['param3' => 'default value']);
        $expectedModifiedData->setModified(true);


        return [
            'typed param' => [
                new ActionData(['param1' => 'stringValue']),
                [$stringRequiredParam],
                new ActionData(['param1' => 'stringValue'])
            ],
            'non-typed param' => [
                new ActionData(['param2' => 'any value']),
                [$mixedRequiredParam],
                new ActionData(['param2' => 'any value'])
            ],
            'optional param' => [
                new ActionData([]),
                [$optionalParam],
                $expectedModifiedData
            ]
        ];
    }


}
