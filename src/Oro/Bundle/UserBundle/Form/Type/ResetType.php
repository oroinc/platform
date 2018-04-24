<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetType extends AbstractType
{
    /** @var string */
    protected $class;

    /** @var PasswordFieldOptionsProvider */
    protected $optionsProvider;

    /**
     * @param string $class User entity class
     * @param PasswordFieldOptionsProvider $optionsProvider
     */
    public function __construct($class, PasswordFieldOptionsProvider $optionsProvider)
    {
        $this->class = $class;
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => PasswordType::class,
            'required'        => true,
            'invalid_message' => 'oro.user.message.password_mismatch',
            'first_options' => [
                'label' => 'oro.user.password.enter_new_password.label',
                'hint' => $this->optionsProvider->getTooltip(),
            ],
            'second_options'  => [
                'label' => 'oro.user.password.enter_new_password_again.label',
            ],
            'error_mapping' => [
                '.' => 'second',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
            'csrf_token_id' => 'reset',
            'dynamic_fields_disabled' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_user_reset';
    }
}
