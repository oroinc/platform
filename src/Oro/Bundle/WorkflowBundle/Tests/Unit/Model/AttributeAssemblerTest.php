<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Attribute;
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

        $assembler = new AttributeAssembler($this->getAttributeGuesser());
        $definition = $this->getWorkflowDefinition();
        $assembler->assemble($definition, $configuration);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflowDefinition()
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        return $definition;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAttributeGuesser()
    {
        $guesser = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeGuesser')
            ->disableOriginalConstructor()
            ->getMock();
        return $guesser;
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return array(
            'no_options' => array(
                array('name' => array('property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "label" is required'
            ),
            'no_type' => array(
                array('name' => array('label' => 'test', 'property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "type" is required'
            ),
            'no_label' => array(
                array('name' => array('type' => 'test', 'property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "label" is required'
            ),
            'invalid_type' => array(
                array('name' => array('label' => 'Label', 'type' => 'text', 'property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
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
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "class" cannot be used in attribute "name"'
            ),
            'missing_object_class' => array(
                array('name' => array('label' => 'Label', 'type' => 'object', 'property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "class" is required in attribute "name"'
            ),
            'missing_entity_class' => array(
                array('name' => array('label' => 'Label', 'type' => 'entity', 'property_path' => null)),
                'Oro\Component\Action\Exception\AssemblerException',
                'Option "class" is required in attribute "name"'
            ),
            'invalid_class' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object', 'options' => array('class' => 'InvalidClass'),
                        'property_path' => null
                    )
                ),
                'Oro\Component\Action\Exception\AssemblerException',
                'Class "InvalidClass" referenced by "class" option in attribute "name" not found'
            ),
            'not_allowed_entity_acl' => array(
                array(
                    'name' => array(
                        'label' => 'Label', 'type' => 'object', 'options' => array('class' => 'stdClass'),
                        'entity_acl' => array('update' => false),
                    )
                ),
                'Oro\Component\Action\Exception\AssemblerException',
                'Attribute "Label" with type "object" can\'t have entity ACL'
            ),
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param Attribute $expectedAttribute
     * @param array $guessedParameters
     */
    public function testAssemble($configuration, $expectedAttribute, array $guessedParameters = array())
    {
        $relatedEntity = '\stdClass';

        $attributeGuesser = $this->getAttributeGuesser();
        $attributeConfiguration = current($configuration);
        if ($guessedParameters && array_key_exists('property_path', $attributeConfiguration)) {
            $attributeGuesser->expects($this->any())
                ->method('guessAttributeParameters')
                ->with($relatedEntity, $attributeConfiguration['property_path'])
                ->will($this->returnValue($guessedParameters));
        }

        $assembler = new AttributeAssembler($attributeGuesser);
        $definition = $this->getWorkflowDefinition();
        $definition->expects($this->once())
            ->method('getEntityAttributeName')
            ->will($this->returnValue('entity_attribute'));
        $definition->expects($this->any())
            ->method('getRelatedEntity')
            ->will($this->returnValue($relatedEntity));

        $expectedAttributesCount = 1;
        if (!array_key_exists('entity_attribute', $configuration)) {
            $expectedAttributesCount++;
        }

        $attributes = $assembler->assemble($definition, $configuration);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $this->assertCount($expectedAttributesCount, $attributes);
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
                array(
                    'attribute_one' => array('label' => 'label', 'type' => 'string', 'property_path' => null)),
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
                        'label' => 'label',
                        'type' => 'object',
                        'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                $this->getAttribute('attribute_one', 'label', 'object', array('class' => 'stdClass'))
            ),
            'entity_minimal' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => array('class' => 'stdClass'),
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
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => array('class' => 'stdClass'),
                        'property_path' => null
                    )
                ),
                $this->getAttribute(
                    'entity_attribute',
                    'label',
                    'entity',
                    array('class' => 'stdClass')
                )
            ),
            'with_entity_acl' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => array('class' => 'stdClass'),
                        'property_path' => null,
                        'entity_acl' => array('update' => false),
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass'),
                    null,
                    array('update' => false)
                )
            ),
            'entity_minimal_guessed_parameters' => array(
                array(
                    'attribute_one' => array(
                        'property_path' => 'entity.field'
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass'),
                    'entity.field'
                ),
                'guessedParameters' => array(
                    'label' => 'label',
                    'type' => 'entity',
                    'options' => array('class' => 'stdClass'),
                ),
            ),
            'entity_full_guessed_parameters' => array(
                array(
                    'attribute_one' => array(
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => array('class' => 'stdClass'),
                        'property_path' => 'entity.field'
                    )
                ),
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    array('class' => 'stdClass'),
                    'entity.field'
                ),
                'guessedParameters' => array(
                    'label' => 'guessed label',
                    'type' => 'object',
                    'options' => array('class' => 'GuessedClass'),
                ),
            ),
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $propertyPath
     * @param array $entityAcl
     * @return Attribute
     */
    protected function getAttribute(
        $name,
        $label,
        $type,
        array $options = array(),
        $propertyPath = null,
        array $entityAcl = array()
    ) {
        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setLabel($label);
        $attribute->setType($type);
        $attribute->setOptions($options);
        $attribute->setPropertyPath($propertyPath);
        $attribute->setEntityAcl($entityAcl);
        return $attribute;
    }
}
