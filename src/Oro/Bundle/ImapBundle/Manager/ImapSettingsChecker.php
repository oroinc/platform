<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

/**
 * Checks that an IMAP connection can be established with provided parameters
 */
class ImapSettingsChecker
{
    /** @var ImapConnectorFactory */
    private $connectorFactory;

    /** @var Mcrypt */
    private $mcrypt;

    /**
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt $mcrypt
     */
    public function __construct(
        ImapConnectorFactory $connectorFactory,
        Mcrypt $mcrypt
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->mcrypt = $mcrypt;
    }

    /**
     * @param UserEmailOrigin $value
     *
     * @return bool
     */
    public function checkConnection(UserEmailOrigin $value)
    {
        $password = $this->mcrypt->decryptData($value->getPassword());
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
