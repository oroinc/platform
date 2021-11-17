<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factory to create an SmtpSettings
 */
class SmtpSettingsFactory
{
    private const REQUEST_HOST = 'host';
    private const REQUEST_PORT = 'port';
    private const REQUEST_ENCRYPTION = 'encryption';
    private const REQUEST_USERNAME = 'username';
    private const REQUEST_PASSWORD = 'password';

    private SymmetricCrypterInterface $encryptor;

    public function __construct(SymmetricCrypterInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public static function createFromRequest(Request $request): SmtpSettings
    {
        return new SmtpSettings(
            $request->get(self::REQUEST_HOST),
            $request->get(self::REQUEST_PORT),
            $request->get(self::REQUEST_ENCRYPTION),
            $request->get(self::REQUEST_USERNAME),
            $request->get(self::REQUEST_PASSWORD)
        );
    }

    public function createFromUserEmailOrigin(UserEmailOrigin $userEmailOrigin): SmtpSettings
    {
        $password = $this->encryptor->decryptData($userEmailOrigin->getPassword());

        return new SmtpSettings(
            $userEmailOrigin->getSmtpHost(),
            $userEmailOrigin->getSmtpPort(),
            $userEmailOrigin->getSmtpEncryption(),
            $userEmailOrigin->getUser(),
            $password
        );
    }

    /**
     * Create SmtpSettings from array with settings
     */
    public function createFromArray($settings): SmtpSettings
    {
        return new SmtpSettings(...$settings);
    }
}
