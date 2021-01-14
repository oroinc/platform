<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationMicrosoftType;
use Oro\Bundle\ImapBundle\Mail\Storage\Office365Imap;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Manager to work with Microsoft OAuth 2 IMAP/SMTP mailings.
 */
class ImapEmailMicrosoftOauth2Manager extends AbstractOauth2Manager
{
    public const OAUTH2_ACCESS_TOKEN_URL = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';

    // Need division of the parameters for two separate access tokens for IMAP/SMTP and user data

    public const OAUTH2_OFFICE365_MAIL_SCOPES = [
        "offline_access",
        "https://outlook.office.com/IMAP.AccessAsUser.All",
        "https://outlook.office.com/POP.AccessAsUser.All",
        "https://outlook.office.com/SMTP.Send",
    ];

    public const OAUTH2_OFFICE365_USER_SCOPES = [
        "openid",
        "offline_access",
        "User.Read",
        "profile"
    ];

    public const RESOURCE_OWNER_OFFICE365 = 'office365';

    /** @var string[] */
    protected $scopes = self::OAUTH2_OFFICE365_MAIL_SCOPES;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    protected $accessTokenUrl = self::OAUTH2_ACCESS_TOKEN_URL;

    public function __construct(
        HttpMethodsClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap,
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        SymmetricCrypterInterface $crypter,
        RouterInterface $router
    ) {
        $this->router = $router;
        parent::__construct($httpClient, $resourceOwnerMap, $configManager, $doctrine, $crypter);
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
    }

    /**
     * {@inheritDoc}
     */
    protected function buildParameters(string $code): array
    {
        return [
            'redirect_uri' => $this->router->generate(
                'oro_imap_microsoft_access_token',
                [],
                RouterInterface::ABSOLUTE_URL
            ),
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
        return self::RESOURCE_OWNER_OFFICE365;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigParameters(): array
    {
        $clientSecretEncrypted = $this->configManager->get('oro_microsoft_integration.client_secret');
        $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
        return [
            'client_id'     => $this->configManager->get('oro_microsoft_integration.client_id'),
            'client_secret' => $clientSecretDecrypted,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenUrl(): string
    {
        $url = parent::getAccessTokenUrl();
        return str_replace(
            '{tenant}',
            $this->configManager->get('oro_microsoft_integration.tenant'),
            $url
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getRefreshTokenParameters(string $refreshToken): array
    {
        $params = parent::getRefreshTokenParameters($refreshToken);
        return array_merge($params, [
            'scope' => $this->getScope()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function isOAuthEnabled(): bool
    {
        return (bool)$this->configManager->get('oro_imap.enable_microsoft_imap');
    }
}
