<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for tracking changes to generic data structures.
 *
 * This type captures and represents changes to data by storing original and modified
 * values in a changeset format. It uses data transformers to convert between array
 * data and JSON representation, making it suitable for hidden fields that track
 * data modifications.
 */
class DataChangesetType extends AbstractType
{
    const NAME = 'oro_data_changeset';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['mapped' => false, 'data_class' => null]);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addViewTransformer(new DataChangesetTransformer())
            ->addViewTransformer(new ArrayToJsonTransformer());
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
