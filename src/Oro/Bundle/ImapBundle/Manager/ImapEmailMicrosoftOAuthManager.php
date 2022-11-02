<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationMicrosoftType;
use Oro\Bundle\ImapBundle\Mail\Storage\Office365Imap;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;

/**
 * Provides functionality to work with Microsoft OAuth IMAP/SMTP mailings.
 */
class ImapEmailMicrosoftOAuthManager extends AbstractOAuthManager
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
        return AccountTypeModel::ACCOUNT_TYPE_MICROSOFT;
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthEnabled(): bool
    {
        return (bool)$this->configManager->get('oro_imap.enable_microsoft_imap');
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionFormTypeClass(): string
    {
        return ConfigurationMicrosoftType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setOriginDefaults(UserEmailOrigin $origin): void
    {
        $origin->setImapHost(Office365Imap::DEFAULT_IMAP_HOST);
        $origin->setImapPort(Office365Imap::DEFAULT_IMAP_PORT);
        $origin->setImapEncryption(Office365Imap::DEFAULT_IMAP_ENCRYPTION);

        $origin->setSmtpHost(Office365Imap::DEFAULT_SMTP_HOST);
        $origin->setSmtpPort(Office365Imap::DEFAULT_SMTP_PORT);
        $origin->setSmtpEncryption(Office365Imap::DEFAULT_SMTP_ENCRYPTION);

        $origin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT);
    }

    /**
     * {@inheritDoc}
     */
    protected function getRefreshAccessTokenScopes(): ?array
    {
        return [
            'offline_access',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/POP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send'
        ];
    }
}
