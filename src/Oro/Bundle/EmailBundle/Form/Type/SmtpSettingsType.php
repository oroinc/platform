<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EmailBundle\Entity\SmtpSettings;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class SmtpSettingsType extends AbstractType
{
    /** @var Mcrypt */
    protected $encryptor;

    /**
     * @param Mcrypt $encryptor
     */
    public function __construct(Mcrypt $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\EmailBundle\Entity\SmtpSettings',
            'cascade_validation' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host', 'text', [
                'label'    => 'oro.email.system_configuration.smtp_settings.host.label',
                'required' => false,
                'attr'     => ['class' => 'critical-field check-connection switchable-field'],
            ])
            ->add('port', 'number', [
                'label'    => 'oro.email.system_configuration.smtp_settings.port.label',
                'attr'     => ['class' => 'check-connection switchable-field'],
                'required' => false
            ])
            ->add('encryption', 'choice', [
                'label'       => 'oro.email.system_configuration.smtp_settings.encryption.label',
                'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
                'attr'        => ['class' => 'check-connection switchable-field'],
                'empty_data'  => null,
                'empty_value' => '',
                'required'    => false
            ])
            ->add('username', 'text', [
                'label'    => 'oro.email.system_configuration.smtp_settings.username.label',
                'required' => true,
                'attr'     => ['class' => 'critical-field check-connection'],
            ])
            ->add('password', 'password', [
                'label' => 'oro.email.system_configuration.smtp_settings.password.label',
                'required' => true,
                'attr' => ['class' => 'check-connection']
            ])
            ->add('passwordHolder', 'hidden', [
                'required' => false,
                'label' => '',
                'mapped' => false
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSet']);
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
        return 'oro_email_smtp_settings';
    }

    /**
     * Set password on form reload
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        /** @var SmtpSettings $data */
        $data = $event->getData();
        $form = $event->getForm();

        if ($data instanceof SmtpSettings && $form->has('passwordHolder')) {
            $form->get('passwordHolder')->setData(
                $this->encryptor->decryptData($data->getPassword())
            );
        }
    }
}
