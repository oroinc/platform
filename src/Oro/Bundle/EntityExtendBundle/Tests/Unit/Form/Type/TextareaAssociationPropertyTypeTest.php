<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\TextareaAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextareaAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new TextareaAssociationPropertyType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_entity_extend_association_property_textarea', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(TextareaType::class, $this->getFormType()->getParent());
    }
}
