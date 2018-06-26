<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\Parameter;

class ParametersResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParametersResolver */
    protected $resolver;

    /** @var array */
    private static $typeAliases = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
    ];

    protected function setUp()
    {
        $this->resolver = new ParametersResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     * @param ActionData $data
     * @param array $parameters
     * @param ActionData $expected
     */
    public function testResolveOk(ActionData $data, array $parameters, ActionData $expected)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ActionGroup $mockActionGroup */
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

        $mixedRequiredParam = new Parameter('param4');

        $optionalParam = new Parameter('param3');
        $optionalParam->setDefault('default value');

        $expectedModifiedData = new ActionData(['param3' => 'default value']);
        $expectedModifiedData->setModified(true);

        return [
            'typed scalar param' => [
                new ActionData(['param1' => 'stringValue']),
                [$stringRequiredParam],
                new ActionData(['param1' => 'stringValue'])
            ],
            'typed class param' => [
                new ActionData(['param2' => $stringRequiredParam]),
                [$this->requiredTypedParameter('param2', 'Oro\Bundle\ActionBundle\Model\Parameter')],
                new ActionData(['param2' => $stringRequiredParam])
            ],
            'typed object param' => [
                new ActionData(['param3' => $stringRequiredParam]),
                [$this->requiredTypedParameter('param3', 'object')],
                new ActionData(['param3' => $stringRequiredParam])
            ],
            'non-typed param' => [
                new ActionData(['param4' => 'any value']),
                [$mixedRequiredParam],
                new ActionData(['param4' => 'any value'])
            ],
            'optional param' => [
                new ActionData([]),
                [$optionalParam],
                $expectedModifiedData
            ]
        ];
    }

    /**
     * @param string $name
     * @param string $type
     * @return Parameter
     */
    private function requiredTypedParameter($name, $type)
    {
        $parameter = new Parameter($name);
        $parameter->setType($type);

        return $parameter;
    }

    /**
     * @dataProvider resolveViolationsTypeProvider
     * @param ActionData $data
     * @param array $parameters
     * @param array $exception
     * @param array $expectedErrors
     */
    public function testResolveViolationType(
        ActionData $data,
        array $parameters,
        array $exception,
        array $expectedErrors
    ) {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ActionGroup $mockActionGroup */
        $mockActionGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $mockActionGroup->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $mockDefinition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupDefinition')
            ->getMock();

        $mockActionGroup->expects($this->once())
            ->method('getDefinition')
            ->willReturn($mockDefinition);

        $mockDefinition->expects($this->once())
            ->method('getName')
            ->willReturn('testActionGroup');

        list($exceptionType, $exceptionMessage) = $exception;

        $errors = new ArrayCollection([]);

        try {
            $this->resolver->resolve($data, $mockActionGroup, $errors);
        } catch (\Exception $exception) {
            $this->assertInstanceOf($exceptionType, $exception);
            $this->assertEquals($exceptionMessage, $exception->getMessage());
        }

        $this->assertEquals($expectedErrors, $errors->getValues());
    }

    /**
     * @return array
     */
    public function resolveViolationsTypeProvider()
    {
        return [
            'bool' => $this->violationTypeProviderArgs(
                'boolean_param',
                123,
                'bool',
                'integer',
                '123'
            ),
            'string' => $this->violationTypeProviderArgs(
                'string_param',
                true,
                'string',
                'boolean',
                'true'
            ),
            'integer' => $this->violationTypeProviderArgs(
                'integer_param',
                false,
                'integer',
                'boolean',
                'false'
            ),
            'array' => $this->violationTypeProviderArgs(
                'array_param',
                'string',
                'array',
                'string',
                '"string"'
            ),
            'object' => $this->violationTypeProviderArgs(
                'object_param',
                [],
                'stdClass',
                'array',
                'array',
                'custom message'
            ),
            'complex object' => $this->violationTypeProviderArgs(
                'complex object comp',
                new ActionData(),
                'Oro\Bundle\ActionBundle\Model\Parameter',
                'Oro\Bundle\ActionBundle\Model\ActionData',
                'Oro\Bundle\ActionBundle\Model\ActionData'
            ),
            'float' => $this->violationTypeProviderArgs(
                'float',
                null,
                'float',
                'NULL',
                'null'
            ),
            'null' => $this->violationTypeProviderArgs(
                'null',
                tmpfile(),
                'null',
                'resource',
                'resource'
            )
        ];
    }

    /**
     * @param string $paramName
     * @param mixed $value
     * @param string $type
     * @param string $gotType
     * @param string $gotValue
     * @param string $customMessage
     * @return array
     */
    private function violationTypeProviderArgs(
        $paramName,
        $value,
        $type,
        $gotType,
        $gotValue,
        $customMessage = null
    ) {
        $typedParam = new Parameter($paramName);
        $typedParam->setType($type);

        if ($customMessage) {
            $typedParam->setMessage($customMessage);
        }

        $msg = $customMessage ?: 'Parameter `{{ parameter }}` validation failure. Reason: {{ reason }}.';

        return [
            'data' => new ActionData([$paramName => $value]),
            'paramDefinitions' => [$typedParam],
            'exception' => [
                'Oro\Component\Action\Exception\InvalidParameterException',
                'Trying to execute ActionGroup "testActionGroup" with invalid or missing parameter(s): ' .
                sprintf('"%s"', $paramName)
            ],
            'errors' => [
                [
                    'message' => $msg,
                    'parameters' => [
                        '{{ reason }}' => sprintf(
                            'Value %s is expected to be of type "%s", but is of type "%s".',
                            $gotValue,
                            array_key_exists($type, self::$typeAliases) ? self::$typeAliases[$type] : $type,
                            $gotType
                        ),
                        '{{ parameter }}' => $paramName,
                        '{{ type }}' => $type,
                        '{{ value }}' => $gotValue,
                    ]
                ]
            ]
        ];
    }
}
