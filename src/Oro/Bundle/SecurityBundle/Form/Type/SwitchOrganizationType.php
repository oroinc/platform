<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Organization switcher form type.
 */
class SwitchOrganizationType extends AbstractType
{
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('organization', OroEntitySelectOrCreateInlineType::class, [

            'required' => true,
            'create_enabled' => false,
            'autocomplete_alias' => 'oro_user_organizations',
            'grid_name' => ' ',
            'configs' => [
                'allowClear' => false
            ],
            'data' => $this->tokenAccessor->getOrganization(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_acl_switch_organization';
    }
}
