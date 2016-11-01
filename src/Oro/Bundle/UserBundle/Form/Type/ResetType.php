<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Validator\Constraints\UsedPassword;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;

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
        $builder->add('plainPassword', 'repeated', [
            'type'            => 'password',
            'required'        => true,
            'first_options' => [
                'label' => 'oro.user.password.enter_new_password.label',
                'attr' => [
                    'data-validation' => $this->optionsProvider->getDataValidationOption(),
                ],
                'hint' => $this->optionsProvider->getTooltip(),
                'hint_position' => 'above',
                'hint_attr' => ['class' => 'oro-hint oro-hint-above'],
            ],
            'second_options'  => [
                'label' => 'oro.user.password.enter_new_password_again.label',
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
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

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $entity = $event->getData();

        if ($entity instanceof UserInterface) {
            $form = $event->getForm();
            // attach a constraint to first_options
            $options = $form->get('plainPassword')->getConfig()->getOptions();
            $options['first_options']['constraints'][] = new UsedPassword(['userId' => $entity->getId()]);
            FormUtils::replaceField($form, 'plainPassword', $options);
        }
    }
}
