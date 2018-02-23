<?php

namespace Oro\Bundle\ImapBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Psr\Log\LoggerInterface;

class GoogleImapConfigHandler
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ImapEmailGoogleOauth2Manager */
    protected $googleImapOauth2Manager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry              $doctrine
     * @param ImapEmailGoogleOauth2Manager $manager
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ImapEmailGoogleOauth2Manager $manager,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->googleImapOauth2Manager = $manager;
        $this->logger = $logger;
    }

    /**
     * @param ConfigManager   $manager
     * @param ConfigChangeSet $changeSet
     */
    public function handle(ConfigManager $manager, ConfigChangeSet $changeSet)
    {
        if ($manager->get('oro_imap.enable_google_imap')) {
            $this->refreshTokens(
                $changeSet->isChanged('oro_google_integration.client_id')
                || $changeSet->isChanged('oro_google_integration.client_secret')
            );
        } else {
            $this->setTokensToNull();
        }
    }

    /**
     * @param bool $force
     */
    protected function refreshTokens($force)
    {
        $em = $this->getEntityManager();

        /** @var UserEmailOrigin[] $origins */
        $origins = $em
            ->getRepository(UserEmailOrigin::class)
            ->getAllOriginsWithRefreshTokens()
            ->getQuery()
            ->getResult();

        $isFlushNeeded = false;
        foreach ($origins as $origin) {
            if ($force || $this->googleImapOauth2Manager->isAccessTokenExpired($origin)) {
                try {
                    $this->googleImapOauth2Manager->refreshAccessToken($origin);
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

    protected function setTokensToNull()
    {
        $em = $this->getEntityManager();

        /** @var UserEmailOrigin[] $origins */
        $origins = $em
            ->getRepository(UserEmailOrigin::class)
            ->getAllOriginsWithAccessTokens()
            ->getQuery()
            ->getResult();

        foreach ($origins as $origin) {
            $origin->setAccessToken(null);
            $origin->setAccessTokenExpiresAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        $em->flush();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(UserEmailOrigin::class);
    }
}
