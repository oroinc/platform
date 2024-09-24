<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigFieldStub extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('value', TextType::class);
        $builder->add('use_parent_scope_value', ParentScopeCheckbox::class, ['value' => 1]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_config_field_stub';
    }
}
