<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapEmailSynchronizer extends AbstractEmailSynchronizer
{
    /** @var ImapEmailSynchronizationProcessorFactory */
    protected $syncProcessorFactory;

    /** @var ImapConnectorFactory */
    protected $connectorFactory;

    /** @var Mcrypt */
    protected $encryptor;

    /**
     * Constructor
     *
     * @param ManagerRegistry                          $doctrine
     * @param KnownEmailAddressCheckerFactory          $knownEmailAddressCheckerFactory
     * @param ImapEmailSynchronizationProcessorFactory $syncProcessorFactory
     * @param ImapConnectorFactory                     $connectorFactory
     * @param Mcrypt                                   $encryptor
     */
    public function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        ImapEmailSynchronizationProcessorFactory $syncProcessorFactory,
        ImapConnectorFactory $connectorFactory,
        Mcrypt $encryptor
    ) {
        parent::__construct($doctrine, $knownEmailAddressCheckerFactory);

        $this->syncProcessorFactory = $syncProcessorFactory;
        $this->connectorFactory     = $connectorFactory;
        $this->encryptor            = $encryptor;
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
    protected function getEmailOriginClass()
    {
        return 'OroImapBundle:ImapEmailOrigin';
    }

    /**
     * Creates a processor is used to synchronize emails
     *
     * @param ImapEmailOrigin $origin
     * @return ImapEmailSynchronizationProcessor
     */
    protected function createSynchronizationProcessor($origin)
    {
        $config = new ImapConfig(
            $origin->getHost(),
            $origin->getPort(),
            $origin->getSsl(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword())
        );

        return $this->syncProcessorFactory->create(
            new ImapEmailManager($this->connectorFactory->createImapConnector($config)),
            $this->getKnownEmailAddressChecker()
        );
    }
}
