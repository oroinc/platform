<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factory to create an SmtpSettings
 */
class SmtpSettingsFactory
{
    const REQUEST_HOST = 'host';
    const REQUEST_PORT = 'port';
    const REQUEST_ENCRYPTION = 'encryption';
    const REQUEST_USERNAME = 'username';
    const REQUEST_PASSWORD = 'password';

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    public function __construct(SymmetricCrypterInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @param Request $request
     *
     * @return SmtpSettings
     */
    public static function createFromRequest(Request $request)
    {
        return new SmtpSettings(
            $request->get(self::REQUEST_HOST),
            $request->get(self::REQUEST_PORT),
            $request->get(self::REQUEST_ENCRYPTION),
            $request->get(self::REQUEST_USERNAME),
            $request->get(self::REQUEST_PASSWORD)
        );
    }

    /**
     * @param UserEmailOrigin $userEmailOrigin
     *
     * @return SmtpSettings
     */
    public function createFromUserEmailOrigin(UserEmailOrigin $userEmailOrigin)
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
     * Create SmtpSettings from array with configuration settings
     *
     * @param array $value
     *
     * @return SmtpSettings
     */
    public function createFromArray(array $value)
    {
        $password = $this->encryptor->decryptData(
            $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PASS)
        );

        return new SmtpSettings(
            $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_HOST),
            $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_PORT),
            $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_ENC),
            $this->getConfigValueByName($value, Configuration::KEY_SMTP_SETTINGS_USER),
            $password
        );
    }

    /**
     * @param mixed $value
     *
     * @return SmtpSettings
     *
     * @throws \InvalidArgumentException
     */
    public function create($value)
    {
        switch (true) {
            case $value instanceof UserEmailOrigin:
                $result = $this->createFromUserEmailOrigin($value);
                break;
            case $value instanceof Request:
                $result = $this->createFromRequest($value);
                break;
            case is_array($value):
                $result = $this->createFromArray($value);
                break;
            default:
                throw new \InvalidArgumentException('Unsupported type');
        }

        return $result;
    }

    /**
     * @param array $data
     * @param string $name
     *
     * @return mixed
     */
    private function getConfigValueByName(array $data, $name)
    {
        $configKey = Configuration::getConfigKeyByName($name, ConfigManager::SECTION_VIEW_SEPARATOR);

        return isset($data[$configKey][ConfigManager::VALUE_KEY]) ?
            $data[$configKey][ConfigManager::VALUE_KEY] : null;
    }
}
