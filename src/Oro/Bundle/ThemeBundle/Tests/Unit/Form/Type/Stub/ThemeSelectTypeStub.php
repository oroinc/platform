<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Stub for ThemeSelectType of FrontendBundle
 */
class ThemeSelectTypeStub extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Default' => 'default',
                'Custom'  => 'custom'
            ],
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_frontend_theme_select';
    }
}
