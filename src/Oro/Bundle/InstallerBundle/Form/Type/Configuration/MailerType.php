<?php

namespace Oro\Bundle\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MailerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_mailer_transport',
                ChoiceType::class,
                array(
                    'label'         => 'form.configuration.mailer.transport',
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices'       => array(
                        'PHP mail' => 'mail',
                        'SMTP' => 'smtp',
                        'sendmail' => 'sendmail',
                    ),
                    'required' => false,
                    'placeholder' => 'None',
                    'client_validation' => false,
                )
            )
            ->add(
                'oro_installer_mailer_host',
                TextType::class,
                array(
                    'label'         => 'form.configuration.mailer.host',
                    'constraints'   => array(
                        new Assert\NotBlank(array('groups' => array('SMTP'))),
                    ),
                )
            )
            ->add(
                'oro_installer_mailer_port',
                IntegerType::class,
                array(
                    'label'         => 'form.configuration.mailer.port',
                    'required'      => false,
                    'constraints'   => array(
                        new Assert\Type(array('groups' => array('SMTP'), 'type' => 'integer')),
                    ),
                )
            )
            ->add(
                'oro_installer_mailer_encryption',
                ChoiceType::class,
                array(
                    'label'         => 'form.configuration.mailer.encryption',
                    'required'      => false,
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices'       => array(
                        'SSL' => 'ssl',
                        'TLS' => 'tls',
                    ),
                    'client_validation' => false,
                    'placeholder'   => 'None'
                )
            )
            ->add(
                'oro_installer_mailer_user',
                TextType::class,
                array(
                    'label'         => 'form.configuration.mailer.user',
                    'required'      => false,
                )
            )
            ->add(
                'oro_installer_mailer_password',
                PasswordType::class,
                array(
                    'label'         => 'form.configuration.mailer.password',
                    'required'      => false,
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();

                return 'smtp' == $data['oro_installer_mailer_transport']
                    ? array('Default', 'SMTP')
                    : array('Default');
            },
        ));
    }

    /**
     * {@inheritdoc}
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
        return 'oro_installer_configuration_mailer';
    }
}
