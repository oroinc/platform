<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Form\Type\TextareaAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextareaAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnArgument(0);

        return new TextareaAssociationPropertyType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_textarea',
            $this->getFormType()->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            TextareaType::class,
            $this->getFormType()->getParent()
        );
    }
}
