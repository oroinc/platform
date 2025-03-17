<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Used in System Configuration to select Roles list
 */
class RoleMultiSelectType extends AbstractType
{
    public function __construct(
        private ManagerRegistry $doctrine
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
                'autocomplete_alias' => 'roles',
                'configs'            => [
                    'multiple'    => true,
                    'placeholder' => 'oro.user.form.choose_role',
                    'allowClear'  => true,
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
        return 'oro_role_multiselect';
    }
}
