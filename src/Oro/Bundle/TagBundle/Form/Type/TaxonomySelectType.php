<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntitySelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting taxonomy entities with autocomplete support.
 *
 * This form type provides an entity selection interface for taxonomy entities with autocomplete functionality.
 * It extends the {@see EntitySelectType} to leverage existing entity selection capabilities and configures
 * autocomplete with a placeholder for improved user experience.
 */
class TaxonomySelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'taxonomy',
                'configs'            => [
                    'placeholder' => 'oro.tag.form.choose_taxonomy'
                ],
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntitySelectType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_taxonomy_select';
    }
}
