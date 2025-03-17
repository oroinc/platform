<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enum id choice type.
 */
class EnumIdChoiceType extends AbstractType
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = $options['multiple']
            ? new EntitiesToIdsTransformer($this->doctrine, EnumOption::class)
            : new EntityToIdTransformer($this->doctrine, EnumOption::class);

        $builder->addModelTransformer(new ReversedTransformer($transformer));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['enum_code']);
        $resolver->setDefaults(['multiple' => true]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EnumChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_enum_id_choice';
    }
}
