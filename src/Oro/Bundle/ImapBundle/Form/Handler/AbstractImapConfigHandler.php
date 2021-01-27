<?php

namespace Oro\Bundle\ImapBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Abstraction for all handlers that take care of refreshing OAuth 2 tokens
 * of user email origins on OAuth application settings changes.
 */
abstract class AbstractImapConfigHandler
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var Oauth2ManagerInterface */
    private $imapOauth2Manager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry              $doctrine
     * @param OAuth2ManagerRegistry        $oauthManagerRegistry
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ManagerRegistry $doctrine,
        OAuth2ManagerRegistry $oauthManagerRegistry,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->imapOauth2Manager = $oauthManagerRegistry->getManager($this->getManagerType());
        $this->logger = $logger;
    }

    /**
     * @param ConfigManager   $manager
     * @param ConfigChangeSet $changeSet
     */
    public function handle(ConfigManager $manager, ConfigChangeSet $changeSet)
    {
        if ($this->imapOauth2Manager->isOAuthEnabled()) {
            $this->refreshTokens($this->isForceRefreshRequired($changeSet));
        } else {
            $this->setTokensToNull();
        }
    }

    /**
     * @param bool $force
     */
    protected function refreshTokens(bool $force): void
    {
        $em = $this->getEntityManager();

        /** @var UserEmailOrigin[] $origins */
        $origins = $em
            ->getRepository(UserEmailOrigin::class)
            ->getAllOriginsWithRefreshTokens($this->imapOauth2Manager->getType())
            ->getQuery()
            ->getResult();

        $isFlushNeeded = false;
        foreach ($origins as $origin) {
            if ($force || $this->imapOauth2Manager->isAccessTokenExpired($origin)) {
                try {
                    $this->imapOauth2Manager->refreshAccessToken($origin);
                } catch (RefreshOAuthAccessTokenFailureException $e) {
                    // if token not updated, not null value must be set
                    $origin->setAccessToken('');

                    $this->logger->warning(
                        'The OAuth2 AccessToken has been cleaned up'
                        . ' due to its expiration and inability to refresh it now.',
                        [
                            'origin'        => (string)$origin,
                            'refresh_token' => $origin->getRefreshToken(),
                            'refresh_error' => $e->getReason()
                        ]
                    );
                }
                $isFlushNeeded = true;
            }
        }

        if ($isFlushNeeded) {
            $em->flush();
        }
    }

    protected function setTokensToNull(): void
    {
        $em = $this->getEntityManager();

        /** @var UserEmailOrigin[] $origins */
        $origins = $em
            ->getRepository(UserEmailOrigin::class)
            ->getAllOriginsWithAccessTokens($this->imapOauth2Manager->getType())
            ->getQuery()
            ->getResult();

        foreach ($origins as $origin) {
            $origin->setAccessToken(null);
            $origin->setAccessTokenExpiresAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        $em->flush();
    }

    /**
     * @return ObjectManager|EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(UserEmailOrigin::class);
    }

    /**
     * Returns manager type name
     *
     * @return string
     */
    abstract protected function getManagerType(): string;

    /**
     * Returns true if refresh token action needs to be forced
     *
     * @param ConfigChangeSet $changeSet
     * @return bool
     */
    abstract protected function isForceRefreshRequired(ConfigChangeSet $changeSet): bool;
}
