<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SmtpType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_smtp';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('enabled', 'checkbox', [
            'label'    => 'oro.email.mailbox.smtp_enabled.label',
            'required' => false,
        ]);
        $builder->add('host', 'text', [
            'label'    => 'oro.email.mailbox.smtp_host.label',
            'required' => false,
        ]);
        $builder->add('port', 'integer', [
            'label'    => 'oro.email.mailbox.smtp_port.label',
            'required' => false,
        ]);
        $builder->add('encryption', 'choice', [
            'label'       => 'oro.email.mailbox.smtp_encryption.label',
            'choices'     => ['ssl' => 'SSL', 'tls' => 'TLS'],
            'empty_data'  => null,
            'empty_value' => '',
            'required'    => false
        ]);
        $builder->add('username', 'text', [
            'label'    => 'oro.email.mailbox.smtp_username.label',
            'required' => false,
        ]);
        $builder->add('password', 'password', [
            'label'    => 'oro.email.mailbox.smtp_password.label',
            'required' => false,
        ]);
    }

}
