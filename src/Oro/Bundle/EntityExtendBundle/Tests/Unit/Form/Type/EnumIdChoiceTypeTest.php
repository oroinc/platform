<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumIdChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumIdChoiceTypeTest extends TypeTestCase
{
    private EnumIdChoiceType $type;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new EnumIdChoiceType($this->createMock(ManagerRegistry::class));
    }

    public function testGetParent()
    {
        $this->assertEquals(EnumChoiceType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['multiple' => true]);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['enum_code']);

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addModelTransformer');

        $this->type->buildForm($builder, ['enum_code' => 'test_enum', 'multiple' => true]);
    }
}
