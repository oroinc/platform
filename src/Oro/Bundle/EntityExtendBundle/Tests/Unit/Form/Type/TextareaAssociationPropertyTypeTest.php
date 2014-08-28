<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\TextareaAssociationPropertyType;

class TextareaAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /** @var TextareaAssociationPropertyType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        $this->type = new TextareaAssociationPropertyType($this->configManager, $entityClassResolver);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_textarea',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'textarea',
            $this->type->getParent()
        );
    }

    /**
     * @return array
     */
    protected function getDisabledFormView()
    {
        return [
            'disabled' => true,
            'attr'     => [],
            'value'    => null
        ];
    }
}
