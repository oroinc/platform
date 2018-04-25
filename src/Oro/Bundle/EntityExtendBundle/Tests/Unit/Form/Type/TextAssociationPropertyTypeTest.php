<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\TextAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        return new TextAssociationPropertyType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_text',
            $this->getFormType()->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            TextType::class,
            $this->getFormType()->getParent()
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
