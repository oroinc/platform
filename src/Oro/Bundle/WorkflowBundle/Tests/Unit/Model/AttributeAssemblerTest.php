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
        $definition = $this->getWorkflowDefinition();
        $assembler->assemble($definition, $configuration);
    }

    protected function getWorkflowDefinition()
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        return $definition;
    }

    /**
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
        $definition = $this->getWorkflowDefinition();
        $definition->expects($this->once())
            ->method('getEntityAttributeName')
            ->will($this->returnValue('entity_attribute'));
        $expectedAttributesCount = 1;
        if (!array_key_exists('entity_attribute', $configuration)) {
            $definition->expects($this->once())
                ->method('getRelatedEntity')
                ->will($this->returnValue('\stdClass'));
            $expectedAttributesCount++;
        } else {
            $definition->expects($this->never())
                ->method('getRelatedEntity');
        }
        $attributes = $assembler->assemble($definition, $configuration);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $this->assertCount($expectedAttributesCount, $attributes);
        $this->assertTrue($attributes->containsKey($expectedAttribute->getName()));

        $this->assertEquals($expectedAttribute, $attributes->get($expectedAttribute->getName()));
    }

    /**
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
                    array('class' => 'stdClass')
                )
            ),
            'with_related_entity' => array(
                array(
                    'entity_attribute' => array(
                        'label' => 'label', 'type' => 'entity', 'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                $this->getAttribute(
                    'entity_attribute',
                    'label',
                    'entity',
                    array('class' => 'stdClass')
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
