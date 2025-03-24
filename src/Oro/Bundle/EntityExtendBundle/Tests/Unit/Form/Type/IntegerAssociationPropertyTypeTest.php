<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\IntegerAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class IntegerAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new IntegerAssociationPropertyType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_entity_extend_association_property_integer', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(IntegerType::class, $this->getFormType()->getParent());
    }
}
