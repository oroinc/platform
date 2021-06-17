<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Model\FormOptionsAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormOptionsAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $actionFactory;

    /** @var ConfigurationPassInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationPass;

    /** @var FormOptionsAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->configurationPass = $this->createMock(ConfigurationPassInterface::class);

        $this->assembler = new FormOptionsAssembler($this->actionFactory);
        $this->assembler->addConfigurationPass($this->configurationPass);
    }

    public function testAssemble()
    {
        $options = [
            'attribute_fields' => [
                'attribute_one' => ['form_type' => 'text'],
                'attribute_two' => ['form_type' => 'text'],
            ],
            'attribute_default_values' => [
                'attribute_one' => '$foo',
                'attribute_two' => '$bar',
            ],
            'form_init' => [
                ['@foo' => 'bar']
            ]
        ];

        $expectedOptions = [
            'attribute_fields' => [
                'attribute_one' => ['form_type' => 'text'],
                'attribute_two' => ['form_type' => 'text'],
            ],
            'attribute_default_values' => [
                'attribute_one' => new PropertyPath('data.foo'),
                'attribute_two' => new PropertyPath('data.bar'),
            ],
            'form_init' => $this->createMock(ActionInterface::class)
        ];

        $attributes = [
            $this->createAttribute('attribute_one'),
            $this->createAttribute('attribute_two'),
        ];

        $this->configurationPass->expects($this->exactly(2))
            ->method('passConfiguration')
            ->withConsecutive(
                [$options['attribute_fields']],
                [$options['attribute_default_values']]
            )
            ->willReturnOnConsecutiveCalls(
                $expectedOptions['attribute_fields'],
                $expectedOptions['attribute_default_values']
            );

        $this->actionFactory->expects($this->once())
            ->method('create')
            ->with(Configurable::ALIAS, $options['form_init'])
            ->willReturn($expectedOptions['form_init']);

        $this->assertEquals(
            $expectedOptions,
            $this->assembler->assemble(
                $options,
                $attributes,
                'step',
                'test'
            )
        );
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(
        array $options,
        array $attributes,
        string $owner,
        string $ownerName,
        string $expectedException,
        string $expectedExceptionMessage
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->assembler->assemble($options, $attributes, $owner, $ownerName);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'string_attribute_fields' => [
                'options' => [
                    'attribute_fields' => 'string'
                ],
                'attributes' => [],
                'owner' => FormOptionsAssembler::STEP_OWNER,
                'ownerName' => 'test',
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Option "form_options.attribute_fields" at step "test" must be an array.'
            ],
            'string_attribute_default_values' => [
                'options' => [
                    'attribute_default_values' => 'string'
                ],
                'attributes' => [],
                'owner' => FormOptionsAssembler::STEP_OWNER,
                'ownerName' => 'test',
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' =>
                    'Option "form_options.attribute_default_values" of step "test" must be an array.'
            ],
            'attribute_not_exist_at_attribute_fields' => [
                'options' => [
                    'attribute_fields' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'owner' => FormOptionsAssembler::STEP_OWNER,
                'ownerName' => 'test',
                'expectedException' => UnknownAttributeException::class,
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one" at step "test".'
            ],
            'attribute_not_exist_at_attribute_default_values' => [
                'options' => [
                    'attribute_default_values' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'owner' => FormOptionsAssembler::STEP_OWNER,
                'ownerName' => 'test',
                'expectedException' => UnknownAttributeException::class,
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one" at step "test".'
            ],
            'attribute_default_value_not_in_attribute_fields' => [
                'options' => [
                    'attribute_fields' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ],
                    'attribute_default_values' => [
                        'attribute_two' => '$attribute_one'
                    ]
                ],
                [
                    $this->createAttribute('attribute_one'),
                    $this->createAttribute('attribute_two'),
                ],
                'owner' => FormOptionsAssembler::STEP_OWNER,
                'ownerName' => 'test',
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' =>
                    'Form options of step "test" doesn\'t have attribute "attribute_two" which is referenced in ' .
                    '"attribute_default_values" option.'
            ],
        ];
    }

    private function createAttribute(string $name): Attribute
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
    }
}
