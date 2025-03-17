<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that provides choice several users
 */
class UserMultiSelectType extends AbstractType
{
    public function __construct(
        protected ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EntitiesToIdsTransformer($this->doctrine, $options['entity_class']));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'users',
                'configs' => [
                    'multiple' => true,
                    'placeholder' => 'oro.user.form.choose_user',
                    'allowClear' => true,
                    'result_template_twig' => '@OroUser/User/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroUser/User/Autocomplete/selection.html.twig',
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_user_multiselect';
    }
}
