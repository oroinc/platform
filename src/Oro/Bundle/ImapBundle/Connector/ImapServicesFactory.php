<?php

namespace Oro\Bundle\ImapBundle\Connector;

use Oro\Bundle\ImapBundle\Connector\Exception\InvalidConfigurationException;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

/**
 * Provides a factory class that creates ImapServices objects.
 */
class ImapServicesFactory
{
    /**
     * An array which is used to choose correct implementation of IMAP storage and search string manager
     *
     * The key is the key capability of IMAP server.
     * The empty key is used for IMAP servers which have not any special preferences
     * The value is an array
     *    the first element if this array is the full name of a class responsible to communication with this IMAP server
     *    the second element if this array is the full name of a class responsible to build the search string
     *
     * @var array
     */
    private $imapServicesMapping;

    /**
     * Default IMAP storage and search string manager
     *
     * The first element if this array is the full name of a class responsible to communication with IMAP server
     * The second element if this array is the full name of a class responsible to build the search string
     *
     * @var array
     */
    private $defaultImapServices;

    public function __construct(array $imapServicesMapping)
    {
        if (!isset($imapServicesMapping[''])) {
            throw new InvalidConfigurationException('The default IMAP services mapping is not found.');
        }
        $this->defaultImapServices = $imapServicesMapping[''];
        unset($imapServicesMapping['']);
        $this->imapServicesMapping = $imapServicesMapping;
    }

    /**
     * @param ImapConfig $config
     * @return ImapServices
     */
    public function createImapServices(ImapConfig $config)
    {
        $defaultImapStorage = $this->getDefaultImapStorage($config);

        $foundItem = $this->findImapServicesConfig($defaultImapStorage->capability());

        $imapStorageClass = null !== $foundItem && strcmp($foundItem[0], \get_class($defaultImapStorage)) !== 0
            ? $foundItem[0]
            : null;
        $searchStringBuilderClass = null !== $foundItem
            ? $foundItem[1]
            : $this->defaultImapServices[1];

        $imapStorage = null !== $imapStorageClass
            ? new $imapStorageClass($defaultImapStorage)
            : $defaultImapStorage;

        return new ImapServices(
            $imapStorage,
            new $searchStringBuilderClass()
        );
    }

    /**
     * @param ImapConfig $config
     * @return Imap
     */
    protected function getDefaultImapStorage(ImapConfig $config)
    {
        $params = [
            'host' => $config->getHost(),
            'port' => $config->getPort(),
            'ssl' => $config->getSsl(),
            'user' => $config->getUser(),
            'password' => $config->getPassword(),
            'accessToken' => $config->getAccessToken(),
            'connectionTimeout' => $config->getConnectionTimeout()
        ];

        $defaultImapStorageClass = $this->defaultImapServices[0];

        return new $defaultImapStorageClass($params);
    }

    /**
     * Finds the configuration of IMAP services for the given IMAP server capabilities
     *
     * @param array $serverCapabilities
     * @return array
     */
    protected function findImapServicesConfig(array $serverCapabilities)
    {
        $result = null;
        foreach ($this->imapServicesMapping as $key => $item) {
            $filterResult = array_filter(
                $serverCapabilities,
                function ($capability) use ($key) {
                    return 0 === strcasecmp($capability, $key);
                }
            );
            if (!empty($filterResult)) {
                $result = $item;
                break;
            }
        }

        return $result;
    }
}
