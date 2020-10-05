<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;

/**
 * Manager to work with Google OAuth 2 mailings via IMAP/SMTP.
 */
class ImapEmailGoogleOauth2Manager extends AbstractOauth2Manager
{
    public const OAUTH2_ACCESS_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';

    public const OAUTH2_GMAIL_SCOPE = 'https://mail.google.com/';

    public const RESOURCE_OWNER_GOOGLE = 'google';

    /** @var string[] */
    protected $scopes = [
        self::OAUTH2_GMAIL_SCOPE
    ];

    /** @var string */
    protected $accessTokenUrl = self::OAUTH2_ACCESS_TOKEN_URL;

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
    }

    /**
     * {@inheritDoc}
     */
    protected function buildParameters(string $code): array
    {
        return [
            'redirect_uri' => 'postmessage',
            'scope' => $this->getScope(),
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourceOwnerName(): string
    {
        return self::RESOURCE_OWNER_GOOGLE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigParameters(): array
    {
        $clientSecretEncrypted = $this->configManager->get('oro_google_integration.client_secret');
        $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
        return [
            'client_id'     => $this->configManager->get('oro_google_integration.client_id'),
            'client_secret' => $clientSecretDecrypted,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthEnabled(): bool
    {
        return $this->configManager->get('oro_imap.enable_google_imap');
    }
}
