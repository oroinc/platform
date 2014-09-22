<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapEmailSynchronizer extends AbstractEmailSynchronizer
{
    /** @var ImapConnectorFactory */
    protected $connectorFactory;

    /** @var Mcrypt */
    protected $encryptor;

    /**
     * Constructor
     *
     * @param ManagerRegistry      $doctrine
     * @param EmailEntityBuilder   $emailEntityBuilder
     * @param EmailAddressManager  $emailAddressManager
     * @param EmailAddressHelper   $emailAddressHelper
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt               $encryptor
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        ImapConnectorFactory $connectorFactory,
        Mcrypt $encryptor
    ) {
        parent::__construct($doctrine, $emailEntityBuilder, $emailAddressManager, $emailAddressHelper);
        $this->connectorFactory = $connectorFactory;
        $this->encryptor        = $encryptor;
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

        return new ImapEmailSynchronizationProcessor(
            $this->log,
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $this->emailAddressManager,
            $this->knownEmailAddressChecker,
            new ImapEmailManager($this->connectorFactory->createImapConnector($config))
        );
    }
}
