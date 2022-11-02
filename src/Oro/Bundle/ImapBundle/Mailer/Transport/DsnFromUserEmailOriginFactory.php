<?php

namespace Oro\Bundle\ImapBundle\Mailer\Transport;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Creates SMTP DSN from {@see UserEmailOrigin}.
 */
class DsnFromUserEmailOriginFactory
{
    private SymmetricCrypterInterface $crypter;

    private OAuthManagerRegistry $oauthManagerRegistry;

    public function __construct(SymmetricCrypterInterface $crypter, OAuthManagerRegistry $oauthManagerRegistry)
    {
        $this->crypter = $crypter;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * Creates SMTP DSN from UserEmailOrigin.
     *
     * @param UserEmailOrigin $userEmailOrigin
     * @return Dsn
     */
    public function create(UserEmailOrigin $userEmailOrigin): Dsn
    {
        return new Dsn(
            strtolower((string)$userEmailOrigin->getSmtpEncryption()) === 'ssl' ? 'smtps' : 'smtp',
            (string)$userEmailOrigin->getSmtpHost(),
            (string)$userEmailOrigin->getUser(),
            $this->getPasswordFromEmailOrigin($userEmailOrigin),
            (int)$userEmailOrigin->getSmtpPort()
        );
    }

    private function getPasswordFromEmailOrigin(UserEmailOrigin $userEmailOrigin): string
    {
        $manager = $this->oauthManagerRegistry->hasManager($userEmailOrigin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($userEmailOrigin->getAccountType())
            : null;

        if (null !== $manager) {
            $accessToken = $manager->getAccessTokenWithCheckingExpiration($userEmailOrigin);
            $password = $accessToken ?? $this->crypter->decryptData($userEmailOrigin->getPassword());
        } else {
            $password = $this->crypter->decryptData($userEmailOrigin->getPassword());
        }

        return $password;
    }
}
