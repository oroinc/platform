<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFolderLoaderInterface;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapEmailFolderLoader implements EmailFolderLoaderInterface
{
    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /** @var Mcrypt */
    protected $encryptor;

    /**
     * Constructor
     *
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt $encryptor
     */
    public function __construct(ImapConnectorFactory $connectorFactory, Mcrypt $encryptor)
    {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof ImapEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function loadEmailFolders(EmailOrigin $origin)
    {
        /** @var ImapEmailOrigin $origin */
        $config = new ImapConfig(
            $origin->getHost(),
            $origin->getPort(),
            $origin->getSsl(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword())
        );

        /** @var ImapEmailFolderManager $manager */
        $manager = new ImapEmailFolderManager($this->connectorFactory->createImapConnector($config));
        /** @var array $folders */
        $folders = $manager->getFolders(null, true);

        return $folders;
    }
}
