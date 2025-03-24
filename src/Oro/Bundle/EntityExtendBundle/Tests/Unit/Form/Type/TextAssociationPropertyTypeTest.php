<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\TextAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new TextAssociationPropertyType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_entity_extend_association_property_text', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(TextType::class, $this->getFormType()->getParent());
    }
}
