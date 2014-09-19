<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;

abstract class AbstractEmailSynchronizationProcessor
{
    /** Determines how many emails can be stored in a database at once */
    const DB_BATCH_SIZE = 30;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * Constructor
     *
     * @param LoggerInterface          $log
     * @param EntityManager            $em
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailAddressManager      $emailAddressManager
     * @param KnownEmailAddressChecker $knownEmailAddressChecker
     */
    protected function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        KnownEmailAddressChecker $knownEmailAddressChecker
    ) {
        $this->log                      = $log;
        $this->em                       = $em;
        $this->emailEntityBuilder       = $emailEntityBuilder;
        $this->emailAddressManager      = $emailAddressManager;
        $this->knownEmailAddressChecker = $knownEmailAddressChecker;
    }

    /**
     * Performs a synchronization of emails for the given email origin.
     *
     * @param EmailOrigin $origin
     * @param \DateTime   $syncStartTime
     */
    abstract public function process(EmailOrigin $origin, $syncStartTime);
}
