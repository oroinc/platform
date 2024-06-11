<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type with string input in tags mode
 */
class Select2TextTagType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'configs' => [
                'minimumInputLength' => 1,
            ],
        ]);

        $resolver->setNormalizer(
            'configs',
            static fn (Options $options, array $configs) => ['tags' => true] + $configs
        );
    }

    public function getParent(): string
    {
        return Select2HiddenType::class;
    }
}
