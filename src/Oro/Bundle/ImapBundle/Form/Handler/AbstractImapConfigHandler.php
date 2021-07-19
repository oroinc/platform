<?php

namespace Oro\Bundle\ImapBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Abstraction for all handlers that take care of refreshing OAuth tokens
 * of user email origins on OAuth application settings changes.
 */
abstract class AbstractImapConfigHandler
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var OAuthManagerInterface */
    private $oauthManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        OAuthManagerRegistry $oauthManagerRegistry,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->oauthManager = $oauthManagerRegistry->getManager($this->getManagerType());
        $this->logger = $logger;
    }

    public function handle(ConfigManager $manager, ConfigChangeSet $changeSet)
    {
        if ($this->oauthManager->isOAuthEnabled()) {
            $this->refreshTokens($this->isForceRefreshRequired($changeSet));
        } else {
            $this->setTokensToNull();
        }
    }

    protected function refreshTokens(bool $force): void
    {
        $em = $this->getEntityManager();

        /** @var UserEmailOrigin[] $origins */
        $origins = $em
            ->getRepository(UserEmailOrigin::class)
            ->getAllOriginsWithRefreshTokens($this->oauthManager->getType())
            ->getQuery()
            ->getResult();

        $isFlushNeeded = false;
        foreach ($origins as $origin) {
            if ($force || $this->oauthManager->isAccessTokenExpired($origin)) {
                try {
                    $this->oauthManager->refreshAccessToken($origin);
                } catch (RefreshOAuthAccessTokenFailureException $e) {
                    // if token not updated, not null value must be set
                    $origin->setAccessToken('');

                    $this->logger->warning(
                        'The OAuth access token has been cleaned up'
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
            ->getAllOriginsWithAccessTokens($this->oauthManager->getType())
            ->getQuery()
            ->getResult();

        foreach ($origins as $origin) {
            $origin->setAccessToken(null);
            $origin->setAccessTokenExpiresAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        $em->flush();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(UserEmailOrigin::class);
    }

    /**
     * Returns manager type name
     */
    abstract protected function getManagerType(): string;

    /**
     * Returns true if refresh token action needs to be forced
     */
    abstract protected function isForceRefreshRequired(ConfigChangeSet $changeSet): bool;
}
