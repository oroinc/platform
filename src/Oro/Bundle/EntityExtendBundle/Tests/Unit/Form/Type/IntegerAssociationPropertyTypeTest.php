<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Form\Type\IntegerAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class IntegerAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): AbstractType
    {
        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnArgument(0);

        return new IntegerAssociationPropertyType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_integer',
            $this->getFormType()->getBlockPrefix()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            IntegerType::class,
            $this->getFormType()->getParent()
        );
    }
}
