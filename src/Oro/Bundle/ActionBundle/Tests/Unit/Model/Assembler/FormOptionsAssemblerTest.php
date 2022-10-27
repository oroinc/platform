<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Exception\UnknownAttributeException;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormOptionsAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationPassInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationPass;

    /** @var FormOptionsAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->configurationPass = $this->createMock(ConfigurationPassInterface::class);

        $this->assembler = new FormOptionsAssembler();
        $this->assembler->addConfigurationPass($this->configurationPass);
    }

    private function createAttribute(string $name): Attribute
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
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

        $this->assertEquals(
            $expectedOptions,
            $this->assembler->assemble($options, $attributes)
        );
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(
        array $options,
        array $attributes,
        string $expectedException,
        string $expectedExceptionMessage
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->assembler->assemble($options, $attributes);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'attribute_not_exist_at_attribute_fields' => [
                'options' => [
                    'attribute_fields' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'expectedException' => UnknownAttributeException::class,
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one".'
            ],
            'attribute_not_exist_at_attribute_default_values' => [
                'options' => [
                    'attribute_default_values' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'expectedException' => UnknownAttributeException::class,
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one".'
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
                'expectedException' => InvalidConfigurationException::class,
                'expectedExceptionMessage' =>
                    'Form options doesn\'t have attribute which is referenced in ' .
                    '"attribute_default_values" option.'
            ],
        ];
    }
}
