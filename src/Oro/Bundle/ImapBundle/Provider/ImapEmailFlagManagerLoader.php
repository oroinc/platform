<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

/**
 * Class ImapEmailFlagManagerLoader
 * @package Oro\Bundle\ImapBundle\Provider
 */
class ImapEmailFlagManagerLoader implements EmailFlagManagerLoaderInterface
{
    /** @var ImapConnectorFactory */
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
    public function select(EmailFolder $folder, OroEntityManager $em)
    {
        /** @var ImapEmailOrigin $origin */
        $origin = $folder->getOrigin();

        $config = new ImapConfig(
            $origin->getHost(),
            $origin->getPort(),
            $origin->getSsl(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword())
        );

        return new ImapEmailFlagManager(
            $this->connectorFactory->createImapConnector($config),
            $em
        );
    }
}
