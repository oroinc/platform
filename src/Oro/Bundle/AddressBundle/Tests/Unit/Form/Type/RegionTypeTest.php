<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var RegionType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new RegionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_region', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [RegionType::COUNTRY_OPTION_KEY => 'test'];

        $builder->expects($this->once())
            ->method('setAttribute')
            ->with(RegionType::COUNTRY_OPTION_KEY, 'test');

        $this->type->buildForm($builder, $options);
    }

    public function testFinishView()
    {
        $optionKey = 'countryFieldName';

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getAttribute')
            ->with(RegionType::COUNTRY_OPTION_KEY)
            ->willReturn($optionKey);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formView = new FormView();
        $this->type->finishView($formView, $form, []);
        $this->assertArrayHasKey('country_field', $formView->vars);
        $this->assertEquals($optionKey, $formView->vars['country_field']);
    }
}
