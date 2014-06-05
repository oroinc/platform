<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssociationChoiceTypeTest extends TypeTestCase
{
    /** @var AssociationChoiceType */
    protected $type;

    protected function setUp()
    {
         $this->type = new AssociationChoiceType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $this->assertEquals(
            [
                'empty_value'                  => false,
                'choices'                      => ['No', 'Yes'],
                'entity_class'                 => null,
                'entity_config_scope'          => null,
                'entity_config_attribute_name' => 'enabled'
            ],
            $resolver->resolve([])
        );
    }

    public function testGetName()
    {
        $this->assertEquals(AssociationChoiceType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
