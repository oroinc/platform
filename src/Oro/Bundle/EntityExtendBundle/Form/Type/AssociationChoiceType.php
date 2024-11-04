<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType as SymfonyChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A form type to toggle associations in entity config
 */
class AssociationChoiceType extends AbstractAssociationType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'placeholder' => false,
                'choices' => [
                    'No' => false,
                    'Yes' => true,
                ],
                'schema_update_required' => function ($newVal, $oldVal) {
                    return true == $newVal && false == $oldVal;
                },
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_association_choice';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyChoiceType::class;
    }
}
