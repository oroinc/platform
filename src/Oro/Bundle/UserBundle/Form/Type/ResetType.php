<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;

class ResetType extends AbstractType
{
    /** @var string */
    protected $class;

    /** @var PasswordTooltipProvider */
    protected $passwordTooltip;

    /**
     * @param string $class User entity class
     */
    public function __construct($class, PasswordTooltipProvider $passwordTooltip)
    {
        $this->class = $class;
        $this->passwordTooltip = $passwordTooltip;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', 'repeated', [
            'type'            => 'password',
            'required'        => true,
            'first_options' => [
                'label' => 'oro.user.password.enter_new_password.label',
                'tooltip' => $this->passwordTooltip->getTooltip(),
            ],
            'second_options'  => ['label' => 'oro.user.password.enter_new_password_again.label'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
            'intention'  => 'reset',
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
