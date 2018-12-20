<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormOptionsAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigurationPassInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configurationPass;

    /**
     * @var FormOptionsAssembler
     */
    protected $assembler;

    protected function setUp()
    {
        $this->configurationPass = $this->createMock(
            'Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface'
        );

        $this->assembler = new FormOptionsAssembler();
        $this->assembler->addConfigurationPass($this->configurationPass);
    }

    public function testAssemble()
    {
        $options = array(
            'attribute_fields' => [
                'attribute_one' => ['form_type' => 'text'],
                'attribute_two' => ['form_type' => 'text'],
            ],
            'attribute_default_values' => [
                'attribute_one' => '$foo',
                'attribute_two' => '$bar',
            ],
        );

        $expectedOptions = array(
            'attribute_fields' => array(
                'attribute_one' => array('form_type' => 'text'),
                'attribute_two' => array('form_type' => 'text'),
            ),
            'attribute_default_values' => array(
                'attribute_one' => new PropertyPath('data.foo'),
                'attribute_two' => new PropertyPath('data.bar'),
            ),
        );

        $attributes = array(
            $this->createAttribute('attribute_one'),
            $this->createAttribute('attribute_two'),
        );

        $this->configurationPass->expects($this->at(0))
            ->method('passConfiguration')
            ->with($options['attribute_fields'])
            ->will($this->returnValue($expectedOptions['attribute_fields']));

        $this->configurationPass->expects($this->at(1))
            ->method('passConfiguration')
            ->with($options['attribute_default_values'])
            ->will($this->returnValue($expectedOptions['attribute_default_values']));

        $this->assertEquals(
            $expectedOptions,
            $this->assembler->assemble(
                $options,
                $attributes
            )
        );
    }

    /**
     * @param array $options
     * @param array $attributes
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     *
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(
        array $options,
        array $attributes,
        $expectedException,
        $expectedExceptionMessage
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->assembler->assemble($options, $attributes);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            'attribute_not_exist_at_attribute_fields' => [
                'options' => [
                    'attribute_fields' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\UnknownAttributeException',
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one".'
            ],
            'attribute_not_exist_at_attribute_default_values' => [
                'options' => [
                    'attribute_default_values' => [
                        'attribute_one' => ['form_type' => 'text'],
                    ]
                ],
                'attributes' => [],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\UnknownAttributeException',
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
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' =>
                    'Form options doesn\'t have attribute which is referenced in ' .
                    '"attribute_default_values" option.'
            ],
        ];
    }

    /**
     * @param string $name
     * @return Attribute
     */
    protected function createAttribute($name)
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
    }
}
