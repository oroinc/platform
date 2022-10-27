<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;

/**
 * The base class for OAuth managers.
 */
abstract class AbstractOAuthManager implements OAuthManagerInterface
{
    private ManagerRegistry $doctrine;
    private OAuthProviderInterface $oauthProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        OAuthProviderInterface $oauthProvider
    ) {
        $this->doctrine = $doctrine;
        $this->oauthProvider = $oauthProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthMode(): string
    {
        return 'XOAUTH2';
    }

    /**
     * {@inheritDoc}
     */
    public function isAccessTokenExpired(UserEmailOrigin $origin): bool
    {
        return $origin->getAccessTokenExpiresAt() < new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin): ?string
    {
        $token = $origin->getAccessToken();

        // if token had been expired, the new one must be generated and saved to the database
        if ($this->isAccessTokenExpired($origin)
            && $this->isOAuthEnabled()
            && $origin->getRefreshToken()
        ) {
            $this->refreshAccessToken($origin);

            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass(ClassUtils::getClass($origin));
            $em->persist($origin);
            $em->flush($origin);

            $token = $origin->getAccessToken();
        }

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshAccessToken(UserEmailOrigin $origin): void
    {
        $refreshToken = $origin->getRefreshToken();
        if (empty($refreshToken)) {
            throw new RefreshOAuthAccessTokenFailureException('The RefreshToken is empty', $refreshToken);
        }

        $response = $this->oauthProvider->getAccessTokenByRefreshToken(
            $refreshToken,
            $this->getRefreshAccessTokenScopes()
        );

        $origin->setAccessToken($response->getAccessToken());
        $origin->setAccessTokenExpiresAt(new \DateTime(
            '+' . ((int)$response->getExpiresIn() - 5) . ' seconds',
            new \DateTimeZone('UTC')
        ));
    }

    protected function getRefreshAccessTokenScopes(): ?array
    {
        return null;
    }
}
