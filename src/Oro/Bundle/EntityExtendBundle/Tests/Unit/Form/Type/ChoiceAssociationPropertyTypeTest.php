<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\ChoiceAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChoiceAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new ChoiceAssociationPropertyType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_entity_extend_association_property_choice', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(ChoiceType::class, $this->getFormType()->getParent());
    }
}
