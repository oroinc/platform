<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\LinkTargetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkTargetTypeTest extends TestCase
{
    private LinkTargetType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new LinkTargetType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_link_target', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $optionResolver = new OptionsResolver();
        $this->type->configureOptions($optionResolver);
        $result = $optionResolver->resolve();

        $this->assertEquals([
            'label' => 'oro.form.link_target.label',
            'tooltip' => 'oro.form.link_target.tooltip',
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'oro.form.link_target.value.same_window' => 1,
                'oro.form.link_target.value.new_window' => 0
            ]
        ], $result);
    }
}
