<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Checks that an IMAP connection can be established with provided parameters
 */
class ImapSettingsChecker
{
    /** @var ImapConnectorFactory */
    private $connectorFactory;

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    public function __construct(
        ImapConnectorFactory $connectorFactory,
        SymmetricCrypterInterface $encryptor
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * @param UserEmailOrigin $value
     *
     * @return bool
     */
    public function checkConnection(UserEmailOrigin $value)
    {
        $password = $this->encryptor->decryptData($value->getPassword());
        $config = new ImapConfig(
            $value->getImapHost(),
            $value->getImapPort(),
            $value->getImapEncryption(),
            $value->getUser(),
            $password
        );

        $isSuccessful = true;
        try {
            $connector = $this->connectorFactory->createImapConnector($config);
            $connector->getCapability();
        } catch (\Exception $e) {
            $isSuccessful = false;
        }

        return $isSuccessful;
    }
}
