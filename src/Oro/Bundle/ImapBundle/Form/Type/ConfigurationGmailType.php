<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines email origin configuration form with predefined
 * Google Gmail IMAP/SMTP settings
 */
class ConfigurationGmailType extends AbstractOAuthAwareConfigurationType
{
    const NAME = 'oro_imap_configuration_gmail';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('clientId', HiddenType::class, [
                'data' => $this->userConfigManager->get('oro_google_integration.client_id')
            ])
            ->add('imapHost', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_HOST
            ])
            ->add('imapPort', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_PORT
            ])
            ->add('user', HiddenType::class, [
                'required' => true,
            ])
            ->add('imapEncryption', HiddenType::class, [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_SSL
            ])
            ->add('smtpHost', HiddenType::class, [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_HOST
            ])
            ->add('smtpPort', HiddenType::class, [
                'required' => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_PORT
            ])
            ->add('smtpEncryption', HiddenType::class, [
                'required'    => false,
                'data' => GmailImap::DEFAULT_GMAIL_SMTP_SSL
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccountType(): string
    {
        return AccountTypeModel::ACCOUNT_TYPE_GMAIL;
    }
}
