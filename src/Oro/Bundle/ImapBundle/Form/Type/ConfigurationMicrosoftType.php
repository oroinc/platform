<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Mail\Storage\Office365Imap;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines email origin configuration form with predefined
 * Microsoft Office 365 IMAP/SMTP settings
 */
class ConfigurationMicrosoftType extends AbstractOAuthAwareConfigurationType
{
    const NAME = 'oro_imap_configuration_microsoft';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('clientId', HiddenType::class, [
                'data' => $this->userConfigManager->get('oro_microsoft_integration.client_id')
            ])->add('tenant', HiddenType::class, [
                'data' => $this->userConfigManager->get('oro_microsoft_integration.tenant')
            ])->add('imapHost', HiddenType::class, [
                'required' => true,
                'data' => Office365Imap::DEFAULT_IMAP_HOST
            ])
            ->add('imapPort', HiddenType::class, [
                'required' => true,
                'data' => Office365Imap::DEFAULT_IMAP_PORT
            ])
            ->add('user', HiddenType::class, [
                'required' => true,
            ])
            ->add('imapEncryption', HiddenType::class, [
                'required' => true,
                'data' => Office365Imap::DEFAULT_IMAP_ENCRYPTION
            ])
            ->add('smtpHost', HiddenType::class, [
                'required' => false,
                'data' => Office365Imap::DEFAULT_SMTP_HOST
            ])
            ->add('smtpPort', HiddenType::class, [
                'required' => false,
                'data' => Office365Imap::DEFAULT_SMTP_PORT
            ])
            ->add('smtpEncryption', HiddenType::class, [
                'required'    => false,
                'data' => Office365Imap::DEFAULT_SMTP_ENCRYPTION
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
        return AccountTypeModel::ACCOUNT_TYPE_MICROSOFT;
    }
}
