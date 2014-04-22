<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;

abstract class AbstractEmailSynchronizationProcessor
{
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

    /**
     * Gets a list of email addresses which have an owner
     * Email addresses are sorted by modification date; newest at the top
     *
     * @return EmailAddress[]
     */
    protected function getKnownEmailAddresses()
    {
        $this->log->notice('Loading known email addresses ...');

        $repo           = $this->emailAddressManager->getEmailAddressRepository($this->em);
        $query          = $repo->createQueryBuilder('a')
            ->select('partial a.{id, email, updated}')
            ->where('a.hasOwner = ?1')
            ->orderBy('a.updated', 'DESC')
            ->setParameter(1, true)
            ->getQuery();
        $emailAddresses = $query->getResult();

        $this->log->notice(sprintf('Loaded %d email address(es).', count($emailAddresses)));

        return $emailAddresses;
    }
}
