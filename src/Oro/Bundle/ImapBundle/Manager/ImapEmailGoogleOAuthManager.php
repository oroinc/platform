<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;

/**
 * Provides functionality to work with Google OAuth mailings via IMAP/SMTP.
 */
class ImapEmailGoogleOAuthManager extends AbstractOAuthManager
{
    private ConfigManager $configManager;

    public function __construct(
        ManagerRegistry $doctrine,
        OAuthProviderInterface $oauthProvider,
        ConfigManager $configManager
    ) {
        parent::__construct($doctrine, $oauthProvider);
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return AccountTypeModel::ACCOUNT_TYPE_GMAIL;
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthEnabled(): bool
    {
        return $this->configManager->get('oro_imap.enable_google_imap');
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionFormTypeClass(): string
    {
        return ConfigurationGmailType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setOriginDefaults(UserEmailOrigin $origin): void
    {
        $origin->setImapHost(GmailImap::DEFAULT_GMAIL_HOST);
        $origin->setImapPort(GmailImap::DEFAULT_GMAIL_PORT);
        $origin->setImapEncryption(GmailImap::DEFAULT_GMAIL_SSL);

        $origin->setSmtpHost(GmailImap::DEFAULT_GMAIL_SMTP_HOST);
        $origin->setSmtpPort(GmailImap::DEFAULT_GMAIL_SMTP_PORT);
        $origin->setSmtpEncryption(GmailImap::DEFAULT_GMAIL_SMTP_SSL);

        $origin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
    }
}
