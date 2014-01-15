<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributeAssembler;

class AttributeAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidOptionsDataProvider
     *
     * @param array $configuration
     * @param string $exception
     * @param string $message
     */
    public function testAssembleRequiredOptionException($configuration, $exception, $message)
    {
        $this->setExpectedException($exception, $message);

        $assembler = new AttributeAssembler();
        $assembler->assemble($configuration);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return array(
            'no_options' => array(
                array('name' => array('property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "label" is required'
            ),
            'no_type' => array(
                array('name' => array('label' => 'test', 'property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "type" is required'
            ),
            'no_label' => array(
                array('name' => array('type' => 'test', 'property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "label" is required'
            ),
            'invalid_type' => array(
                array('name' => array('label' => 'Label', 'type' => 'text', 'property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Invalid attribute type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                    '"float", "string", "array", "object", "entity"'
            ),
            'invalid_type_class' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'string', 'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "class" cannot be used in attribute "name"'
            ),
            'missing_object_class' => array(
                array('name' => array('label' => 'Label', 'type' => 'object', 'property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "class" is required in attribute "name"'
            ),
            'missing_entity_class' => array(
                array('name' => array('label' => 'Label', 'type' => 'entity', 'property_path' => null)),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "class" is required in attribute "name"'
            ),
            'invalid_class' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object', 'options' => array('class' => 'InvalidClass'),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Class "InvalidClass" referenced by "class" option in attribute "name" not found'
            ),
            'object_managed_entity' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object',
                        'options' => array('class' => 'DateTime', 'managed_entity' => true),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "managed_entity" cannot be used in attribute "name"'
            ),
            'object_multiple' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object',
                        'options' => array('class' => 'DateTime', 'multiple' => true),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "multiple" cannot be used in attribute "name"'
            ),
            'object_bind' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object',
                        'options' => array('class' => 'DateTime', 'bind' => true),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Option "bind" cannot be used in attribute "name"'
            ),
            'entity_bind_and_multiple_false' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'entity',
                        'options' => array(
                            'class' => 'DateTime', 'managed_entity' => true, 'bind' => false, 'multiple' => false
                        ),
                        'property_path' => null
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Options "multiple" and "bind" for managed entity in attribute "name" ' .
                    'cannot be both false simultaneously'
            ),
            'property_path for managed entity' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'entity',
                        'options' => array(
                            'class' => 'DateTime', 'managed_entity' => true
                        ),
                        'property_path' => 'test'
                    )
                ),
                'Oro\Bundle\WorkflowBundle\Exception\AssemblerException',
                'Property path can not be set for managed entity in attribute "name"'
            )
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param Attribute $expectedAttribute
     */
    public function testAssemble($configuration, $expectedAttribute)
    {
        $assembler = new AttributeAssembler();
        $attributes = $assembler->assemble($configuration);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes->containsKey($expectedAttribute->getName()));

        $this->assertEquals($expectedAttribute, $attributes->get($expectedAttribute->getName()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationDataProvider()
    {
        return array(
            'string' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'string', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'string')
            ),
            'bool' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'bool', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'bool')
            ),
            'boolean' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'boolean', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'boolean')
            ),
            'int' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'int', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'int')
            ),
            'integer' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'integer', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'integer')
            ),
            'float' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'float', 'property_path' => null)),
                $this->getAttribute('attribute_one', 'label', 'float')
            ),
            'array' => array(
                array('attribute_one' => array('label' => 'label', 'type' => 'array', 'property_path' => null)
                ),
                $this->getAttribute('attribute_one', 'label', 'array')
            ),
            'object' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label', 'type' => 'object', 'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                $this->getAttribute('attribute_one', 'label', 'object', array('class' => 'stdClass'))
            ),
            'entity_minimal' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label', 'type' => 'entity', 'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass', 'multiple' => false, 'bind' => false)
                )
            ),
            'managed_entity_minimal' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => array('class' => 'stdClass', 'managed_entity' => true),
                        'property_path' => null
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass', 'managed_entity' => true, 'multiple' => false, 'bind' => true)
                )
            ),
            'entity_full' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label', 'type' => 'entity',
                        'options' => array('class' => 'stdClass', 'multiple' => true, 'bind' => false),
                        'property_path' => 'test'
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass', 'multiple' => true, 'bind' => false),
                    'test'
                )
            ),
            'entity_multiple_and_bind' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label', 'type' => 'entity',
                        'options' => array('class' => 'stdClass', 'multiple' => true, 'bind' => true),
                        'property_path' => null
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass', 'multiple' => true, 'bind' => true)
                )
            )
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $propertyPath
     * @return Attribute
     */
    protected function getAttribute($name, $label, $type, array $options = array(), $propertyPath = null)
    {
        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setLabel($label);
        $attribute->setType($type);
        $attribute->setOptions($options);
        $attribute->setPropertyPath($propertyPath);
        return $attribute;
    }
}
