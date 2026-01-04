<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetEntityJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provide select role
 * create select field
 */
class WidgetRoleSelectType extends AbstractType
{
    public const NAME = 'oro_type_widget_role_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'widget_owner_roles',
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
        return WidgetEntityJquerySelect2HiddenType::class;
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
}
