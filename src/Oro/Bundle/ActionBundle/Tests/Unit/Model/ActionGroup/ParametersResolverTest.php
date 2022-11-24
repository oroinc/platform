<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\Parameter;
use Oro\Component\Action\Exception\InvalidParameterException;

class ParametersResolverTest extends \PHPUnit\Framework\TestCase
{
    private static array $typeAliases = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
    ];

    /** @var ParametersResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ParametersResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolveOk(ActionData $data, array $parameters, ActionData $expected)
    {
        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->resolver->resolve($data, $actionGroup);

        $this->assertEquals($data, $expected);
    }

    public function resolveDataProvider(): array
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
                [$this->requiredTypedParameter('param2', Parameter::class)],
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

    private function requiredTypedParameter(string $name, string $type): Parameter
    {
        $parameter = new Parameter($name);
        $parameter->setType($type);

        return $parameter;
    }

    /**
     * @dataProvider resolveViolationsTypeProvider
     */
    public function testResolveViolationType(
        ActionData $data,
        array $parameters,
        array $exception,
        array $expectedErrors
    ) {
        $definition = $this->createMock(ActionGroupDefinition::class);
        $definition->expects($this->once())
            ->method('getName')
            ->willReturn('testActionGroup');

        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $actionGroup->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        [$exceptionType, $exceptionMessage] = $exception;

        $errors = new ArrayCollection([]);

        try {
            $this->resolver->resolve($data, $actionGroup, $errors);
        } catch (\Exception $exception) {
            $this->assertInstanceOf($exceptionType, $exception);
            $this->assertEquals($exceptionMessage, $exception->getMessage());
        }

        $this->assertEquals($expectedErrors, $errors->getValues());
    }

    public function resolveViolationsTypeProvider(): array
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
                Parameter::class,
                ActionData::class,
                ActionData::class
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

    private function violationTypeProviderArgs(
        string $paramName,
        mixed $value,
        string $type,
        string $gotType,
        string $gotValue,
        string $customMessage = null
    ): array {
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
                InvalidParameterException::class,
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
